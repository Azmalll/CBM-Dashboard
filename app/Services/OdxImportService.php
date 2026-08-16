<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\MeasurementPoint;
use App\Models\MeasurementResult;
use App\Models\Inspection;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OdxImportService
{
    private const IMPORT_BATCH_SIZE = 500;

    public function __construct(
        private OdxParser $parser
    ) {
    }

    /**
     * Import ODX.
     *
     * Optimized for large Omnitrend ODX files:
     * - ODX is still parsed completely by OdxParser.
     * - Repeated Equipment / Inspection / Equipment Inspection /
     *   Measurement Point queries are cached in memory.
     * - Existing Measurement Result keys are loaded in batches.
     * - New Measurement Results are inserted in bulk.
     * - Equipment Inspection summary is refreshed once per inspection.
     *
     * This avoids the previous N x queries-per-measurement pattern
     * that could keep a Vercel Function alive until the 300s timeout.
     */
    public function import(string $filePath): array
    {
        if (!is_file($filePath)) {
            throw new RuntimeException(
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
                'message' => 'Tidak ada data Overall Velocity yang ditemukan dari file ODX.',
                'imported' => 0,
                'updated' => 0,
                'total' => 0,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 2. CACHE ENTITY
        |--------------------------------------------------------------------------
        |
        | Entity lookup sebelumnya dilakukan berulang untuk SETIAP measurement.
        | Pada file besar hal ini menghasilkan ribuan / puluhan ribu query.
        |
        | Sekarang setiap entity hanya dicari sekali selama satu import.
        |--------------------------------------------------------------------------
        */

        $inspectionCache = [];
        $equipmentCache = [];
        $equipmentInspectionCache = [];
        $measurementPointCache = [];

        $preparedRows = [];
        $equipmentInspectionIds = [];

        /*
        |--------------------------------------------------------------------------
        | 3. PREPARE / RESOLVE MASTER DATA
        |--------------------------------------------------------------------------
        */

        foreach ($rows as $data) {
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

            $measurementCarbon = Carbon::parse(
                $measurementDatetime
            );

            $measurementDatetime =
                $measurementCarbon->format('Y-m-d H:i:s');

            $measurementDate =
                $measurementCarbon->toDateString();

            /*
            |--------------------------------------------------------------------------
            | PARSE PATH
            |--------------------------------------------------------------------------
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

            if (!$equipmentIdCode) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | INSPECTION
            |--------------------------------------------------------------------------
            |
            | Cache berdasarkan tanggal.
            |--------------------------------------------------------------------------
            */

            if (!array_key_exists($measurementDate, $inspectionCache)) {
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

                $inspectionCache[$measurementDate] =
                    $inspection;
            }

            $inspection =
                $inspectionCache[$measurementDate];

            /*
            |--------------------------------------------------------------------------
            | EQUIPMENT
            |--------------------------------------------------------------------------
            |
            | Cache berdasarkan equipment_id dari ODX.
            |--------------------------------------------------------------------------
            */

            if (!array_key_exists($equipmentIdCode, $equipmentCache)) {
                $equipment =
                    Equipment::where(
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
                    | Update hanya jika memang berubah.
                    |--------------------------------------------------------------------------
                    */

                    $changed = false;

                    if (
                        $equipment->equipment_name !==
                        $equipmentName
                    ) {
                        $equipment->equipment_name =
                            $equipmentName;

                        $changed = true;
                    }

                    if (
                        $area !== null &&
                        $area !== '' &&
                        $equipment->area !== $area
                    ) {
                        $equipment->area =
                            $area;

                        $changed = true;
                    }

                    if (
                        $plant !== null &&
                        $plant !== '' &&
                        $equipment->plant !== $plant
                    ) {
                        $equipment->plant =
                            $plant;

                        $changed = true;
                    }

                    if (
                        $machineType !== null &&
                        $machineType !== '' &&
                        $equipment->machine_type !== $machineType
                    ) {
                        $equipment->machine_type =
                            $machineType;

                        $changed = true;
                    }

                    if ($changed) {
                        $equipment->save();
                    }
                }

                $equipmentCache[$equipmentIdCode] =
                    $equipment;
            }

            $equipment =
                $equipmentCache[$equipmentIdCode];

            /*
            |--------------------------------------------------------------------------
            | EQUIPMENT INSPECTION
            |--------------------------------------------------------------------------
            */

            $equipmentInspectionKey =
                $inspection->id . '|' . $equipment->id;

            if (
                !array_key_exists(
                    $equipmentInspectionKey,
                    $equipmentInspectionCache
                )
            ) {
                $equipmentInspection =
                    DB::table('equipment_inspections')
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
                    $now = now();

                    $equipmentInspectionId =
                        DB::table('equipment_inspections')
                        ->insertGetId([
                            'inspection_id' =>
                                $inspection->id,

                            'equipment_id' =>
                                $equipment->id,

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
                                $now,

                            'updated_at' =>
                                $now,
                        ]);

                    $equipmentInspection = (object) [
                        'id' =>
                            $equipmentInspectionId,
                    ];
                }

                $equipmentInspectionCache[
                    $equipmentInspectionKey
                ] = $equipmentInspection;
            }

            $equipmentInspection =
                $equipmentInspectionCache[
                    $equipmentInspectionKey
                ];

            $equipmentInspectionId =
                (int) $equipmentInspection->id;

            $equipmentInspectionIds[
                $equipmentInspectionId
            ] = true;

            /*
            |--------------------------------------------------------------------------
            | MEASUREMENT POINT
            |--------------------------------------------------------------------------
            |
            | Cache berdasarkan equipment + point.
            |--------------------------------------------------------------------------
            */

            $normalizedPointName =
                strtolower(
                    trim($pointName)
                );

            $measurementPointKey =
                $equipment->id . '|' .
                $normalizedPointName;

            if (
                !array_key_exists(
                    $measurementPointKey,
                    $measurementPointCache
                )
            ) {
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

                $direction =
                    $this->detectDirection(
                        $pointName
                    );

                $location =
                    $machineType ?: 'Unknown';

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
                    | Update hanya jika memang berubah.
                    |--------------------------------------------------------------------------
                    */

                    $changed = false;

                    if (
                        $measurementPoint->location !==
                        $location
                    ) {
                        $measurementPoint->location =
                            $location;

                        $changed = true;
                    }

                    if (
                        $measurementPoint->direction !==
                        $direction
                    ) {
                        $measurementPoint->direction =
                            $direction;

                        $changed = true;
                    }

                    if (
                        $measurementPoint->active !== true
                    ) {
                        $measurementPoint->active =
                            true;

                        $changed = true;
                    }

                    if ($changed) {
                        $measurementPoint->save();
                    }
                }

                $measurementPointCache[
                    $measurementPointKey
                ] = $measurementPoint;
            }

            $measurementPoint =
                $measurementPointCache[
                    $measurementPointKey
                ];

            /*
            |--------------------------------------------------------------------------
            | SIMPAN ROW YANG SUDAH RESOLVED
            |--------------------------------------------------------------------------
            */

            $preparedRows[] = [
                'inspection_id' =>
                    (int) $inspection->id,

                'equipment_inspection_id' =>
                    $equipmentInspectionId,

                'measurement_point_id' =>
                    (int) $measurementPoint->id,

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
        }

        unset(
            $rows,
            $inspectionCache,
            $equipmentCache,
            $equipmentInspectionCache,
            $measurementPointCache
        );

        if (empty($preparedRows)) {
            return [
                'message' => 'Tidak ada measurement valid yang dapat diimport dari file ODX.',
                'imported' => 0,
                'updated' => 0,
                'total' => 0,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 4. LOAD EXISTING MEASUREMENT RESULTS
        |--------------------------------------------------------------------------
        |
        | Sebelumnya ada query:
        |
        | MeasurementResult::where(...)->where(...)->where(...)->first()
        |
        | untuk SETIAP row.
        |
        | Sekarang existing key diambil secara batch per Equipment Inspection.
        |--------------------------------------------------------------------------
        */

        $datetimesByEquipmentInspection = [];

        foreach ($preparedRows as $row) {
            $equipmentInspectionId =
                (int) $row['equipment_inspection_id'];

            $datetimesByEquipmentInspection[
                $equipmentInspectionId
            ][] =
                $row['measurement_datetime'];
        }

        $existingResults = [];

        foreach (
            $datetimesByEquipmentInspection
            as $equipmentInspectionId => $datetimes
        ) {
            $datetimes =
                array_values(
                    array_unique($datetimes)
                );

            foreach (
                array_chunk(
                    $datetimes,
                    self::IMPORT_BATCH_SIZE
                ) as $datetimeChunk
            ) {
                $query =
                    DB::table('measurement_results')
                    ->select([
                        'id',
                        'inspection_id',
                        'equipment_inspection_id',
                        'measurement_point_id',
                        'measurement_datetime',
                    ])
                    ->where(
                        'equipment_inspection_id',
                        $equipmentInspectionId
                    )
                    ->whereIn(
                        'measurement_datetime',
                        $datetimeChunk
                    )
                    ->get();

                foreach ($query as $existing) {
                    $key =
                        $this->measurementResultKey(
                            (int) $existing->equipment_inspection_id,
                            (int) $existing->measurement_point_id,
                            (string) $existing->measurement_datetime
                        );

                    $existingResults[$key] =
                        $existing;
                }
            }
        }

        unset($datetimesByEquipmentInspection);

        /*
        |--------------------------------------------------------------------------
        | 5. BUILD INSERT / UPDATE DATA
        |--------------------------------------------------------------------------
        |
        | Inspector sengaja TIDAK disentuh.
        |--------------------------------------------------------------------------
        */

        $insertRowsByKey = [];
        $updateRows = [];
        $duplicateUpdates = 0;

        $now = now();

        foreach ($preparedRows as $row) {
            $key =
                $this->measurementResultKey(
                    $row['equipment_inspection_id'],
                    $row['measurement_point_id'],
                    $row['measurement_datetime']
                );

            if (isset($existingResults[$key])) {
                /*
                | Row memang sudah ada di database.
                | Setiap kemunculan berikutnya diperlakukan sebagai UPDATE,
                | sama seperti perilaku importer lama.
                */
                $updateRows[] = [
                    'id' =>
                        (int) $existingResults[$key]->id,

                    'inspection_id' =>
                        $row['inspection_id'],

                    'equipment_inspection_id' =>
                        $row['equipment_inspection_id'],

                    'measurement_point_id' =>
                        $row['measurement_point_id'],

                    'measurement_datetime' =>
                        $row['measurement_datetime'],

                    'measurement_date' =>
                        $row['measurement_date'],

                    'overall_velocity' =>
                        $row['overall_velocity'],

                    'unit' =>
                        $row['unit'],

                    'peak_value' =>
                        $row['peak_value'],

                    'crest_factor' =>
                        $row['crest_factor'],
                ];

                continue;
            }

            /*
            | Key yang sama bisa muncul lebih dari sekali
            | dalam satu file ODX.
            |
            | Importer lama:
            |   row pertama  -> INSERT
            |   row berikut  -> UPDATE
            |
            | Jadi jangan membuat duplicate INSERT.
            */
            if (isset($insertRowsByKey[$key])) {
                $insertRowsByKey[$key] = array_merge(
                    $insertRowsByKey[$key],
                    [
                        'overall_velocity' =>
                            $row['overall_velocity'],

                        'unit' =>
                            $row['unit'],

                        'peak_value' =>
                            $row['peak_value'],

                        'crest_factor' =>
                            $row['crest_factor'],

                        'updated_at' =>
                            $now,
                    ]
                );

                $duplicateUpdates++;

                continue;
            }

            $insertRowsByKey[$key] = [
                'inspection_id' =>
                    $row['inspection_id'],

                'equipment_inspection_id' =>
                    $row['equipment_inspection_id'],

                'measurement_point_id' =>
                    $row['measurement_point_id'],

                'measurement_datetime' =>
                    $row['measurement_datetime'],

                'measurement_date' =>
                    $row['measurement_date'],

                /*
                | Inspector tetap NULL untuk data baru.
                */
                'inspector' =>
                    null,

                'overall_velocity' =>
                    $row['overall_velocity'],

                'unit' =>
                    $row['unit'],

                'peak_value' =>
                    $row['peak_value'],

                'crest_factor' =>
                    $row['crest_factor'],

                'created_at' =>
                    $now,

                'updated_at' =>
                    $now,
            ];
        }

        unset(
            $preparedRows,
            $existingResults
        );

        $insertRows =
            array_values(
                $insertRowsByKey
            );

        unset($insertRowsByKey);

        /*
        |--------------------------------------------------------------------------
        | 6. BULK INSERT
        |--------------------------------------------------------------------------
        |
        | INSERT dilakukan per 500 row agar query tidak terlalu besar.
        |--------------------------------------------------------------------------
        */

        $imported = 0;

        foreach (
            array_chunk(
                $insertRows,
                self::IMPORT_BATCH_SIZE
            ) as $insertChunk
        ) {
            DB::transaction(function () use ($insertChunk) {
                DB::table('measurement_results')
                    ->insert($insertChunk);
            });

            $imported += count($insertChunk);
        }

        unset($insertRows);

        /*
        |--------------------------------------------------------------------------
        | 7. UPDATE EXISTING
        |--------------------------------------------------------------------------
        |
        | Update hanya dilakukan untuk row yang memang sudah ada.
        | Biasanya jauh lebih sedikit daripada INSERT.
        |--------------------------------------------------------------------------
        */

        $updated = $duplicateUpdates;

        foreach ($updateRows as $update) {
            DB::table('measurement_results')
                ->where('id', $update['id'])
                ->update([
                    'inspection_id' =>
                        $update['inspection_id'],

                    'equipment_inspection_id' =>
                        $update['equipment_inspection_id'],

                    'measurement_point_id' =>
                        $update['measurement_point_id'],

                    'measurement_datetime' =>
                        $update['measurement_datetime'],

                    'measurement_date' =>
                        $update['measurement_date'],

                    'overall_velocity' =>
                        $update['overall_velocity'],

                    'unit' =>
                        $update['unit'],

                    'peak_value' =>
                        $update['peak_value'],

                    'crest_factor' =>
                        $update['crest_factor'],

                    'updated_at' =>
                        now(),
                ]);

            $updated++;
        }

        unset($updateRows);

        /*
        |--------------------------------------------------------------------------
        | 8. REFRESH EQUIPMENT INSPECTION SUMMARY
        |--------------------------------------------------------------------------
        |
        | Satu kali per equipment inspection, bukan satu kali per measurement.
        |--------------------------------------------------------------------------
        */

        foreach (
            array_keys($equipmentInspectionIds)
            as $equipmentInspectionId
        ) {
            $this->refreshEquipmentInspectionSummary(
                (int) $equipmentInspectionId
            );
        }

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

    private function measurementResultKey(
        int $equipmentInspectionId,
        int $measurementPointId,
        string $measurementDatetime
    ): string {
        return
            $equipmentInspectionId . '|' .
            $measurementPointId . '|' .
            $measurementDatetime;
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
            'H' => 'Horizontal',
            'V' => 'Vertical',
            'A' => 'Axial',
            'P' => 'PeakVue',
            default => null,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | REFRESH EQUIPMENT INSPECTION SUMMARY
    |--------------------------------------------------------------------------
    */

    private function refreshEquipmentInspectionSummary(
        int $equipmentInspectionId
    ): void {
        $highestMeasurement =
            DB::table('measurement_results')
                ->where(
                    'equipment_inspection_id',
                    $equipmentInspectionId
                )
                ->orderByDesc('overall_velocity')
                ->orderBy('id')
                ->first();

        if (!$highestMeasurement) {
            DB::table('equipment_inspections')
                ->where('id', $equipmentInspectionId)
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

        DB::table('equipment_inspections')
            ->where('id', $equipmentInspectionId)
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