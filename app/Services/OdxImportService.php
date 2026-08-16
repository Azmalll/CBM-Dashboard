<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\MeasurementPoint;
use App\Models\MeasurementResult;
use App\Models\Inspection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OdxImportService
{
    public function __construct(
        private OdxParser $parser
    ) {
    }

    /**
     * Import file ODX.
     *
     * Alur:
     * ODX
     * -> Inspection
     * -> Equipment
     * -> Equipment Inspection
     * -> Measurement Point
     * -> Measurement Result
     *
     * Inspector TIDAK ditentukan oleh ODX.
     * Inspector akan di-assign secara manual pada Measurement Result.
     */
    public function import(string $filePath): array
    {
        if (!is_file($filePath)) {
            throw new \RuntimeException(
                'File ODX tidak ditemukan atau tidak dapat dibaca.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 1. PARSE ODX
        |--------------------------------------------------------------------------
        */

        $rows = $this->parser->parseOverallVelocity($filePath);

        if (empty($rows)) {
            return [
                'message' =>
                    'Tidak ada data Overall Velocity yang ditemukan dari file ODX.',

                'imported' => 0,

                'updated' => 0,

                'total' => 0,
            ];
        }

        $imported = 0;
        $updated = 0;

        /*
        |--------------------------------------------------------------------------
        | EQUIPMENT INSPECTION YANG PERLU DI-REFRESH
        |--------------------------------------------------------------------------
        |
        | Sebelumnya refreshEquipmentInspectionSummary()
        | dipanggil pada setiap measurement.
        |
        | Jika ada ribuan measurement, query summary juga dilakukan ribuan kali.
        |
        | Sekarang kita hanya mencatat ID-nya terlebih dahulu.
        | Summary akan dihitung sekali setelah seluruh row selesai diproses.
        |
        */

        $equipmentInspectionIds = [];

        /*
        |--------------------------------------------------------------------------
        | 2. TRANSACTION
        |--------------------------------------------------------------------------
        */

        DB::transaction(function () use (
            $rows,
            &$imported,
            &$updated,
            &$equipmentInspectionIds
        ) {

            foreach ($rows as $data) {

                /*
                |--------------------------------------------------------------------------
                | DATA DASAR
                |--------------------------------------------------------------------------
                */

                $equipmentName = trim(
                    $data['equipment_name'] ?? ''
                );

                $pointName = trim(
                    $data['measurement_point_name'] ?? ''
                );

                $measurementDatetime =
                    $data['measurement_datetime'] ?? null;

                $overallVelocity =
                    isset($data['overall_velocity'])
                        ? (float) $data['overall_velocity']
                        : 0.00;

                if (
                    $equipmentName === '' ||
                    $pointName === '' ||
                    !$measurementDatetime
                ) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | NORMALIZE DATETIME
                |--------------------------------------------------------------------------
                */

                $measurementCarbon = Carbon::parse(
                    $measurementDatetime
                );

                $measurementDatetime =
                    $measurementCarbon->format('Y-m-d H:i:s');

                $measurementDate =
                    $measurementCarbon->toDateString();

                /*
                |--------------------------------------------------------------------------
                | PARSE PATH ODX
                |--------------------------------------------------------------------------
                |
                | Contoh:
                |
                | Minas
                | CEOR
                | P-2102
                | Motor
                | MOH
                | 102 Overall velocity >120
                |
                */

                $pathInfo = $this->parsePath(
                    $data['path'] ?? ''
                );

                $plant =
                    $pathInfo['plant'];

                $area =
                    $pathInfo['area'];

                $equipmentIdCode =
                    $pathInfo['equipment_id'];

                $machineType =
                    $pathInfo['machine_type'];

                /*
                |--------------------------------------------------------------------------
                | 3. INSPECTION
                |--------------------------------------------------------------------------
                |
                | Inspection hanya digunakan sebagai session/tanggal.
                |
                | Inspector TIDAK diambil dari ODX.
                |
                */

                $inspection =
                    Inspection::where(
                        'inspection_date',
                        $measurementDate
                    )
                    ->orderBy('id')
                    ->first();

                if (!$inspection) {

                    $inspection = new Inspection();

                    $inspection->inspection_date =
                        $measurementDate;

                    $inspection->inspector =
                        'Unassigned';

                    $inspection->remarks =
                        'Inspection session created from ODX import. Inspector is assigned per measurement.';

                    $inspection->save();
                }

                /*
                |--------------------------------------------------------------------------
                | 4. EQUIPMENT
                |--------------------------------------------------------------------------
                */

                $equipment = Equipment::where(
                    'equipment_id',
                    $equipmentIdCode
                )->first();

                if (!$equipment) {

                    $equipment = new Equipment();

                    $equipment->equipment_id =
                        $equipmentIdCode;

                    $equipment->equipment_name =
                        $equipmentName;

                    $equipment->area =
                        $area;

                    $equipment->plant =
                        $plant;

                    $equipment->machine_type =
                        $machineType ?: 'Unknown';

                    $equipment->priority =
                        'Medium';

                    $equipment->status =
                        $this->determineSeverity(
                            $overallVelocity
                        );

                    $equipment->save();

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | Update informasi equipment jika data ODX
                    | lebih lengkap.
                    |--------------------------------------------------------------------------
                    */

                    $equipment->equipment_name =
                        $equipmentName;

                    if (
                        $area !== null &&
                        $area !== ''
                    ) {
                        $equipment->area =
                            $area;
                    }

                    if (
                        $plant !== null &&
                        $plant !== ''
                    ) {
                        $equipment->plant =
                            $plant;
                    }

                    if (
                        $machineType !== null &&
                        $machineType !== ''
                    ) {
                        $equipment->machine_type =
                            $machineType;
                    }

                    $equipment->save();
                }

                /*
                |--------------------------------------------------------------------------
                | 5. EQUIPMENT INSPECTION
                |--------------------------------------------------------------------------
                */

                $equipmentInspection =
                    DB::table(
                        'equipment_inspections'
                    )
                    ->where(
                        'inspection_id',
                        $inspection->id
                    )
                    ->where(
                        'equipment_id',
                        $equipment->id
                    )
                    ->first();

                if (!$equipmentInspection) {

                    $equipmentInspectionId =
                        DB::table(
                            'equipment_inspections'
                        )->insertGetId([

                            'inspection_id' =>
                                $inspection->id,

                            'equipment_id' =>
                                $equipment->id,

                            /*
                            | Summary akan dihitung ulang
                            | setelah seluruh measurement selesai.
                            */

                            'highest_overall' =>
                                0.00,

                            'highest_point_id' =>
                                null,

                            'severity' =>
                                'Pending',

                            'diagnosis' =>
                                null,

                            'recommendation' =>
                                null,

                            'report_file' =>
                                null,

                            'created_at' =>
                                now(),

                            'updated_at' =>
                                now(),
                        ]);

                    $equipmentInspection =
                        DB::table(
                            'equipment_inspections'
                        )
                        ->where(
                            'id',
                            $equipmentInspectionId
                        )
                        ->first();
                }

                /*
                |--------------------------------------------------------------------------
                | CATAT ID UNTUK FINAL SUMMARY
                |--------------------------------------------------------------------------
                */

                $equipmentInspectionIds[
                    (int) $equipmentInspection->id
                ] = true;

                /*
                |--------------------------------------------------------------------------
                | 6. MEASUREMENT POINT
                |--------------------------------------------------------------------------
                */

                $direction =
                    $this->detectDirection(
                        $pointName
                    );

                $location =
                    $machineType ?: 'Unknown';

                /*
                |--------------------------------------------------------------------------
                | Cari measurement point existing secara aman.
                |
                | Identitas:
                | equipment_id + point_name
                |--------------------------------------------------------------------------
                */

                $normalizedPointName =
                    strtolower(
                        trim($pointName)
                    );

                $measurementPoint =
                    MeasurementPoint::where(
                        'equipment_id',
                        $equipment->id
                    )
                    ->whereRaw(
                        'LOWER(TRIM(point_name)) = ?',
                        [$normalizedPointName]
                    )
                    ->orderBy('id')
                    ->first();

                if (!$measurementPoint) {

                    $measurementPoint =
                        new MeasurementPoint();

                    $measurementPoint->equipment_id =
                        $equipment->id;

                    $measurementPoint->point_name =
                        $pointName;

                    $measurementPoint->location =
                        $location;

                    $measurementPoint->direction =
                        $direction;

                    $measurementPoint->active =
                        true;

                    $measurementPoint->save();

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | Point sudah ada:
                    | jangan create point baru.
                    |--------------------------------------------------------------------------
                    */

                    $measurementPoint->location =
                        $location;

                    $measurementPoint->direction =
                        $direction;

                    $measurementPoint->active =
                        true;

                    $measurementPoint->save();
                }

                /*
                |--------------------------------------------------------------------------
                | 7. MEASUREMENT RESULT
                |--------------------------------------------------------------------------
                |
                | KEY:
                | equipment_inspection_id
                | measurement_point_id
                | measurement_datetime
                |
                | Sama datetime:
                | UPDATE
                |
                | Berbeda datetime:
                | INSERT
                |
                | Inspector:
                | TIDAK diubah oleh ODX.
                |--------------------------------------------------------------------------
                */

                $existingResult =
                    MeasurementResult::where(
                        'equipment_inspection_id',
                        $equipmentInspection->id
                    )
                    ->where(
                        'measurement_point_id',
                        $measurementPoint->id
                    )
                    ->where(
                        'measurement_datetime',
                        $measurementDatetime
                    )
                    ->first();

                /*
                |--------------------------------------------------------------------------
                | DATA MEASUREMENT
                |--------------------------------------------------------------------------
                */

                $resultData = [

                    'inspection_id' =>
                        $inspection->id,

                    'equipment_inspection_id' =>
                        $equipmentInspection->id,

                    'measurement_point_id' =>
                        $measurementPoint->id,

                    'measurement_datetime' =>
                        $measurementDatetime,

                    'measurement_date' =>
                        $measurementDate,

                    'overall_velocity' =>
                        $overallVelocity,

                    'unit' =>
                        $data['unit'] ??
                        'mm/s RMS',

                    'peak_value' =>
                        $data['peak_value'] ??
                        null,

                    'crest_factor' =>
                        $data['crest_factor'] ??
                        null,
                ];

                /*
                |--------------------------------------------------------------------------
                | UPDATE EXISTING
                |--------------------------------------------------------------------------
                */

                if ($existingResult) {

                    /*
                    |--------------------------------------------------------------------------
                    | Inspector sengaja TIDAK disentuh.
                    |--------------------------------------------------------------------------
                    */

                    $existingResult->inspection_id =
                        $resultData['inspection_id'];

                    $existingResult->equipment_inspection_id =
                        $resultData['equipment_inspection_id'];

                    $existingResult->measurement_point_id =
                        $resultData['measurement_point_id'];

                    $existingResult->measurement_datetime =
                        $resultData['measurement_datetime'];

                    $existingResult->measurement_date =
                        $resultData['measurement_date'];

                    $existingResult->overall_velocity =
                        $resultData['overall_velocity'];

                    $existingResult->unit =
                        $resultData['unit'];

                    $existingResult->peak_value =
                        $resultData['peak_value'];

                    $existingResult->crest_factor =
                        $resultData['crest_factor'];

                    $existingResult->save();

                    $updated++;

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | INSERT NEW
                    |--------------------------------------------------------------------------
                    */

                    $result =
                        new MeasurementResult();

                    $result->inspection_id =
                        $resultData['inspection_id'];

                    $result->equipment_inspection_id =
                        $resultData['equipment_inspection_id'];

                    $result->measurement_point_id =
                        $resultData['measurement_point_id'];

                    $result->measurement_datetime =
                        $resultData['measurement_datetime'];

                    $result->measurement_date =
                        $resultData['measurement_date'];

                    /*
                    | Inspector otomatis NULL.
                    | Akan diisi manual nanti.
                    */

                    $result->inspector =
                        null;

                    $result->overall_velocity =
                        $resultData['overall_velocity'];

                    $result->unit =
                        $resultData['unit'];

                    $result->peak_value =
                        $resultData['peak_value'];

                    $result->crest_factor =
                        $resultData['crest_factor'];

                    $result->save();

                    $imported++;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | 8. FINAL REFRESH EQUIPMENT INSPECTION SUMMARY
            |--------------------------------------------------------------------------
            |
            | PENTING:
            |
            | Sebelumnya fungsi ini dipanggil setiap measurement.
            |
            | Sekarang hanya dipanggil satu kali untuk setiap
            | equipment inspection yang terlibat.
            |
            */

            foreach (
                array_keys($equipmentInspectionIds)
                as $equipmentInspectionId
            ) {

                $this->refreshEquipmentInspectionSummary(
                    (int) $equipmentInspectionId
                );
            }
        });

        /*
        |--------------------------------------------------------------------------
        | RETURN RESULT
        |--------------------------------------------------------------------------
        */

        return [
            'message' =>
                'Import ODX berhasil.',

            'imported' =>
                $imported,

            'updated' =>
                $updated,

            'total' =>
                $imported + $updated,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | PARSE PATH ODX
    |--------------------------------------------------------------------------
    */

    private function parsePath(string $path): array
    {
        $parts = preg_split(
            '/\\\\/',
            trim($path)
        );

        $parts = array_values(
            array_filter(
                $parts,
                fn ($value) =>
                    trim($value) !== ''
            )
        );

        return [
            'plant' =>
                $parts[0] ?? null,

            'area' =>
                $parts[1] ?? null,

            'equipment_id' =>
                $parts[2] ?? null,

            'machine_type' =>
                $parts[3] ?? null,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | DETECT DIRECTION
    |--------------------------------------------------------------------------
    */

    private function detectDirection(
        string $pointName
    ): ?string {

        $pointName =
            strtoupper(
                trim($pointName)
            );

        return match (
            substr($pointName, -1)
        ) {

            'H' =>
                'Horizontal',

            'V' =>
                'Vertical',

            'A' =>
                'Axial',

            'P' =>
                'PeakVue',

            default =>
                null,
        };
    }


    /*
    |--------------------------------------------------------------------------
    | REFRESH EQUIPMENT INSPECTION SUMMARY
    |--------------------------------------------------------------------------
    |
    | Fungsi ini sekarang dipanggil SATU KALI per equipment inspection
    | setelah seluruh measurement selesai di-import.
    |--------------------------------------------------------------------------
    */

    private function refreshEquipmentInspectionSummary(
        int $equipmentInspectionId
    ): void {

        $highestMeasurement =
            DB::table(
                'measurement_results'
            )
            ->where(
                'equipment_inspection_id',
                $equipmentInspectionId
            )
            ->orderByDesc(
                'overall_velocity'
            )
            ->orderBy(
                'id'
            )
            ->first();

        if (!$highestMeasurement) {

            DB::table(
                'equipment_inspections'
            )
            ->where(
                'id',
                $equipmentInspectionId
            )
            ->update([

                'highest_overall' =>
                    0.00,

                'highest_point_id' =>
                    null,

                'severity' =>
                    'Pending',

                'updated_at' =>
                    now(),
            ]);

            return;
        }

        $highestOverall =
            (float) $highestMeasurement->overall_velocity;

        DB::table(
            'equipment_inspections'
        )
        ->where(
            'id',
            $equipmentInspectionId
        )
        ->update([

            'highest_overall' =>
                $highestOverall,

            'highest_point_id' =>
                $highestMeasurement->measurement_point_id,

            'severity' =>
                $this->determineSeverity(
                    $highestOverall
                ),

            'updated_at' =>
                now(),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | SEVERITY - OMNITREND 10816-3 PROFILE
    |--------------------------------------------------------------------------
    |
    | < 2.80      Normal
    | < 4.50      Alert
    | < 7.10      Danger
    | >= 7.10     Critical
    |--------------------------------------------------------------------------
    */

    private const OMNITREND_10816_3_LIMITS = [
        'normal_max' => 2.80,
        'alert_max' => 4.50,
        'danger_max' => 7.10,
    ];


    private function determineSeverity(
        float $overall
    ): string {

        if (
            $overall <
            self::OMNITREND_10816_3_LIMITS['normal_max']
        ) {
            return 'Normal';
        }

        if (
            $overall <
            self::OMNITREND_10816_3_LIMITS['alert_max']
        ) {
            return 'Alert';
        }

        if (
            $overall <
            self::OMNITREND_10816_3_LIMITS['danger_max']
        ) {
            return 'Danger';
        }

        return 'Critical';
    }
}