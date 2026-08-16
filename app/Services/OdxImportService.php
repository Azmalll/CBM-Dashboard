<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\MeasurementPoint;
use App\Models\Inspection;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OdxImportService
{
    /**
     * Batch size hanya untuk ukuran query.
     *
     * PENTING:
     * Semua batch tetap berada di DALAM SATU transaction.
     *
     * Jadi:
     *
     * batch 1 OK
     * batch 2 OK
     * batch 3 ERROR
     *
     * => seluruh import ROLLBACK.
     */
    private const BATCH_SIZE = 500;

    public function __construct(
        private OdxParser $parser
    ) {
    }

    /**
     * Import ODX Overall Velocity.
     *
     * Measurement identity:
     *
     * equipment_inspection_id
     * + measurement_point_id
     * + measurement_datetime
     *
     * Timestamp berbeda = measurement berbeda.
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

        $rows = $this->parser->parseOverallVelocity(
            $filePath
        );

        $parserCount = count($rows);

        if ($parserCount === 0) {
            return [
                'message' =>
                    'Tidak ada data Overall Velocity yang ditemukan dari file ODX.',

                'imported' => 0,
                'updated' => 0,
                'total' => 0,
                'parser_count' => 0,
                'verified_count' => 0,
            ];
        }

        Log::info('ODX parser completed.', [
            'file' => basename($filePath),
            'parser_count' => $parserCount,
        ]);

        /*
        |--------------------------------------------------------------------------
        | 2. SATU TRANSACTION UNTUK SELURUH IMPORT
        |--------------------------------------------------------------------------
        */

        return DB::transaction(
            function () use (
                $rows,
                $parserCount
            ) {
                /*
                |--------------------------------------------------------------------------
                | CACHE MASTER DATA
                |--------------------------------------------------------------------------
                */

                $inspectionCache = [];
                $equipmentCache = [];
                $equipmentInspectionCache = [];
                $measurementPointCache = [];

                /*
                |--------------------------------------------------------------------------
                | HASIL YANG AKAN DITULIS
                |--------------------------------------------------------------------------
                */

                $measurementRows = [];

                $equipmentInspectionIds = [];

                /*
                |--------------------------------------------------------------------------
                | 3. PREPARE SELURUH ROW
                |--------------------------------------------------------------------------
                */

                foreach ($rows as $index => $data) {

                    $rowNumber = $index + 1;

                    /*
                    |--------------------------------------------------------------------------
                    | DATA DASAR
                    |--------------------------------------------------------------------------
                    */

                    $equipmentName = trim(
                        (string) (
                            $data['equipment_name'] ?? ''
                        )
                    );

                    $pointName = trim(
                        (string) (
                            $data['measurement_point_name'] ?? ''
                        )
                    );

                    $rawDatetime =
                        $data['measurement_datetime'] ?? null;

                    if (
                        $equipmentName === '' ||
                        $pointName === '' ||
                        !$rawDatetime
                    ) {
                        throw new RuntimeException(
                            "ODX row #{$rowNumber} tidak valid: " .
                            "equipment, measurement point, atau datetime kosong."
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | DATETIME
                    |--------------------------------------------------------------------------
                    */

                    try {
                        $measurementCarbon =
                            Carbon::parse(
                                $rawDatetime
                            );
                    } catch (\Throwable $e) {
                        throw new RuntimeException(
                            "ODX row #{$rowNumber} memiliki datetime tidak valid: " .
                            $rawDatetime,
                            previous: $e
                        );
                    }

                    $measurementDatetime =
                        $measurementCarbon->format(
                            'Y-m-d H:i:s'
                        );

                    $measurementDate =
                        $measurementCarbon->toDateString();

                    /*
                    |--------------------------------------------------------------------------
                    | PATH
                    |--------------------------------------------------------------------------
                    */

                    $pathInfo =
                        $this->parsePath(
                            (string) (
                                $data['path'] ?? ''
                            )
                        );

                    $plant =
                        $pathInfo['plant'];

                    $area =
                        $pathInfo['area'];

                    $equipmentIdCode =
                        $pathInfo['equipment_id'];

                    $machineType =
                        $pathInfo['machine_type'];

                    if (
                        !$equipmentIdCode
                    ) {
                        throw new RuntimeException(
                            "ODX row #{$rowNumber}: " .
                            "Equipment ID tidak ditemukan dari path."
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | OVERALL VELOCITY
                    |--------------------------------------------------------------------------
                    |
                    | Ini adalah nilai RMS yang dipilih parser
                    | dari Overall Velocity.
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !array_key_exists(
                            'overall_velocity',
                            $data
                        )
                    ) {
                        throw new RuntimeException(
                            "ODX row #{$rowNumber}: " .
                            "Overall Velocity tidak ditemukan."
                        );
                    }

                    $overallVelocity =
                        (float) $data['overall_velocity'];

                    /*
                    |--------------------------------------------------------------------------
                    | 4. INSPECTION
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !isset(
                            $inspectionCache[
                                $measurementDate
                            ]
                        )
                    ) {
                        $inspection =
                            Inspection::where(
                                'inspection_date',
                                $measurementDate
                            )
                            ->orderBy('id')
                            ->first();

                        if (!$inspection) {
                            $inspection =
                                new Inspection();

                            $inspection->inspection_date =
                                $measurementDate;

                            $inspection->inspector =
                                'Unassigned';

                            $inspection->remarks =
                                'Inspection session created from ODX import. Inspector is assigned per measurement.';

                            $inspection->save();
                        }

                        $inspectionCache[
                            $measurementDate
                        ] = $inspection;
                    }

                    $inspection =
                        $inspectionCache[
                            $measurementDate
                        ];

                    /*
                    |--------------------------------------------------------------------------
                    | 5. EQUIPMENT
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !isset(
                            $equipmentCache[
                                $equipmentIdCode
                            ]
                        )
                    ) {
                        $equipment =
                            Equipment::where(
                                'equipment_id',
                                $equipmentIdCode
                            )->first();

                        if (!$equipment) {
                            $equipment =
                                new Equipment();

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

                        $equipmentCache[
                            $equipmentIdCode
                        ] = $equipment;
                    }

                    $equipment =
                        $equipmentCache[
                            $equipmentIdCode
                        ];

                    /*
                    |--------------------------------------------------------------------------
                    | 6. EQUIPMENT INSPECTION
                    |--------------------------------------------------------------------------
                    */

                    $equipmentInspectionKey =
                        $inspection->id .
                        '|' .
                        $equipment->id;

                    if (
                        !isset(
                            $equipmentInspectionCache[
                                $equipmentInspectionKey
                            ]
                        )
                    ) {
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
                            ->lockForUpdate()
                            ->first();

                        if (!$equipmentInspection) {

                            $now = now();

                            $equipmentInspectionId =
                                DB::table(
                                    'equipment_inspections'
                                )->insertGetId([
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

                            $equipmentInspection =
                                DB::table(
                                    'equipment_inspections'
                                )
                                ->where(
                                    'id',
                                    $equipmentInspectionId
                                )
                                ->lockForUpdate()
                                ->first();
                        }

                        $equipmentInspectionCache[
                            $equipmentInspectionKey
                        ] =
                            $equipmentInspection;
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
                    | 7. MEASUREMENT POINT
                    |--------------------------------------------------------------------------
                    */

                    $normalizedPointName =
                        strtolower(
                            trim($pointName)
                        );

                    $measurementPointKey =
                        $equipment->id .
                        '|' .
                        $normalizedPointName;

                    if (
                        !isset(
                            $measurementPointCache[
                                $measurementPointKey
                            ]
                        )
                    ) {
                        $measurementPoint =
                            MeasurementPoint::where(
                                'equipment_id',
                                $equipment->id
                            )
                            ->whereRaw(
                                'LOWER(TRIM(point_name)) = ?',
                                [
                                    $normalizedPointName
                                ]
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
                                $measurementPoint->active !==
                                true
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
                        ] =
                            $measurementPoint;
                    }

                    $measurementPoint =
                        $measurementPointCache[
                            $measurementPointKey
                        ];

                    /*
                    |--------------------------------------------------------------------------
                    | 8. PREPARE MEASUREMENT RESULT
                    |--------------------------------------------------------------------------
                    */

                    $measurementRows[] = [

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

                        /*
                         * Inspector TIDAK diubah oleh ODX.
                         */
                        'inspector' =>
                            null,

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

                        'created_at' =>
                            now(),

                        'updated_at' =>
                            now(),
                    ];
                }

                /*
                |--------------------------------------------------------------------------
                | 9. PREPARE COUNT HARUS SAMA
                |--------------------------------------------------------------------------
                */

                $preparedCount =
                    count($measurementRows);

                if (
                    $preparedCount !==
                    $parserCount
                ) {
                    throw new RuntimeException(
                        'ODX integrity gagal: parser membaca ' .
                        $parserCount .
                        ' row, tetapi hanya ' .
                        $preparedCount .
                        ' row berhasil dipersiapkan.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | 10. EXACT DUPLICATE CHECK
                |--------------------------------------------------------------------------
                |
                | Timestamp berbeda tetap berbeda.
                |
                | Duplicate hanya jika:
                |
                | inspection + point + datetime
                | semuanya sama.
                |--------------------------------------------------------------------------
                */

                $uniqueKeys = [];

                foreach (
                    $measurementRows as $row
                ) {
                    $key =
                        $this->measurementKey(
                            $row
                        );

                    if (
                        isset(
                            $uniqueKeys[$key]
                        )
                    ) {
                        throw new RuntimeException(
                            'ODX integrity gagal: ' .
                            'duplicate exact measurement ditemukan: ' .
                            $key
                        );
                    }

                    $uniqueKeys[$key] = true;
                }

                $expectedCount =
                    count($uniqueKeys);

                if (
                    $expectedCount !==
                    $parserCount
                ) {
                    throw new RuntimeException(
                        'ODX integrity gagal: ' .
                        'jumlah unique measurement (' .
                        $expectedCount .
                        ') tidak sama dengan parser (' .
                        $parserCount .
                        ').'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | 11. BULK UPSERT
                |--------------------------------------------------------------------------
                |
                | Semua batch berada dalam transaction yang sama.
                |--------------------------------------------------------------------------
                */

                foreach (
                    array_chunk(
                        $measurementRows,
                        self::BATCH_SIZE
                    ) as $chunk
                ) {
                    DB::table(
                        'measurement_results'
                    )->upsert(
                        $chunk,
                        [
                            'equipment_inspection_id',
                            'measurement_point_id',
                            'measurement_datetime',
                        ],
                        [
                            'inspection_id',
                            'measurement_date',
                            'overall_velocity',
                            'unit',
                            'peak_value',
                            'crest_factor',
                            'updated_at',
                        ]
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | 12. VERIFY DI DALAM TRANSACTION
                |--------------------------------------------------------------------------
                |
                | BELUM COMMIT.
                |
                | Kalau verification gagal:
                | throw -> ROLLBACK SEMUANYA.
                |--------------------------------------------------------------------------
                */

                $verifiedCount = 0;

                $rowsByInspection =
                    [];

                foreach (
                    $measurementRows as $row
                ) {
                    $rowsByInspection[
                        (int) $row[
                            'equipment_inspection_id'
                        ]
                    ][] =
                        $row;
                }

                foreach (
                    $rowsByInspection
                    as $equipmentInspectionId =>
                    $expectedRows
                ) {
                    $expectedKeys =
                        [];

                    $datetimeList =
                        [];

                    foreach (
                        $expectedRows as $row
                    ) {
                        $key =
                            $this->measurementKey(
                                $row
                            );

                        $expectedKeys[
                            $key
                        ] = true;

                        $datetimeList[
                            $row[
                                'measurement_datetime'
                            ]
                        ] = true;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Ambil measurement yang benar-benar
                    | ada di DB untuk session tersebut.
                    |--------------------------------------------------------------------------
                    */

                    $actualRows =
                        DB::table(
                            'measurement_results'
                        )
                        ->select([
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
                            array_keys(
                                $datetimeList
                            )
                        )
                        ->get();

                    $actualKeys =
                        [];

                    foreach (
                        $actualRows as $actual
                    ) {
                        $key =
                            $actual->equipment_inspection_id .
                            '|' .
                            $actual->measurement_point_id .
                            '|' .
                            $actual->measurement_datetime;

                        if (
                            isset(
                                $expectedKeys[$key]
                            )
                        ) {
                            $actualKeys[
                                $key
                            ] = true;
                        }
                    }

                    $missing =
                        array_diff_key(
                            $expectedKeys,
                            $actualKeys
                        );

                    if (
                        !empty($missing)
                    ) {
                        throw new RuntimeException(
                            'ODX integrity verification gagal. ' .
                            'Equipment inspection ' .
                            $equipmentInspectionId .
                            ' kehilangan ' .
                            count($missing) .
                            ' measurement. ' .
                            'Seluruh import di-rollback.'
                        );
                    }

                    $verifiedCount +=
                        count($expectedKeys);
                }

                /*
                |--------------------------------------------------------------------------
                | 13. VERIFY TOTAL
                |--------------------------------------------------------------------------
                */

                if (
                    $verifiedCount !==
                    $expectedCount
                ) {
                    throw new RuntimeException(
                        'ODX integrity verification gagal: ' .
                        'expected ' .
                        $expectedCount .
                        ', verified ' .
                        $verifiedCount .
                        '. Seluruh import di-rollback.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | 14. REFRESH SUMMARY
                |--------------------------------------------------------------------------
                */

                foreach (
                    array_keys(
                        $equipmentInspectionIds
                    ) as $equipmentInspectionId
                ) {
                    $this->refreshEquipmentInspectionSummary(
                        (int) $equipmentInspectionId
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | 15. FINAL LOG
                |--------------------------------------------------------------------------
                */

                Log::info(
                    'ODX import integrity verified.',
                    [
                        'parser_count' =>
                            $parserCount,

                        'expected_count' =>
                            $expectedCount,

                        'verified_count' =>
                            $verifiedCount,

                        'equipment_inspections' =>
                            count(
                                $equipmentInspectionIds
                            ),
                    ]
                );

                /*
                |--------------------------------------------------------------------------
                | 16. COMMIT TERJADI OTOMATIS
                |--------------------------------------------------------------------------
                |
                | Kalau kita sampai sini:
                |
                | parser = expected = verified
                |
                | DB::transaction() akan COMMIT.
                |--------------------------------------------------------------------------
                */

                return [
                    'message' =>
                        'Import ODX berhasil. ' .
                        $verifiedCount .
                        '/' .
                        $expectedCount .
                        ' measurement terverifikasi.',

                    'imported' =>
                        $parserCount,

                    'updated' =>
                        0,

                    'total' =>
                        $expectedCount,

                    'parser_count' =>
                        $parserCount,

                    'verified_count' =>
                        $verifiedCount,
                ];
            },
            3
        );
    }

    /*
    |--------------------------------------------------------------------------
    | MEASUREMENT UNIQUE KEY
    |--------------------------------------------------------------------------
    */

    private function measurementKey(
        array $row
    ): string {
        return
            (int) $row[
                'equipment_inspection_id'
            ]
            . '|'
            .
            (int) $row[
                'measurement_point_id'
            ]
            . '|'
            .
            (string) $row[
                'measurement_datetime'
            ];
    }

    /*
    |--------------------------------------------------------------------------
    | PARSE PATH
    |--------------------------------------------------------------------------
    */

    private function parsePath(
        string $path
    ): array {
        $parts =
            preg_split(
                '/\\\\/',
                trim($path)
            );

        $parts =
            array_values(
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
    | REFRESH SUMMARY
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
            (float)
            $highestMeasurement->overall_velocity;

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
    | SEVERITY
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
            self::OMNITREND_10816_3_LIMITS[
                'normal_max'
            ]
        ) {
            return 'Normal';
        }

        if (
            $overall <
            self::OMNITREND_10816_3_LIMITS[
                'alert_max'
            ]
        ) {
            return 'Alert';
        }

        if (
            $overall <
            self::OMNITREND_10816_3_LIMITS[
                'danger_max'
            ]
        ) {
            return 'Danger';
        }

        return 'Critical';
    }
}