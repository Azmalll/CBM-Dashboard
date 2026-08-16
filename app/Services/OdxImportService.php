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
    private const BATCH_SIZE = 500;

    public function __construct(
        private OdxParser $parser
    ) {
    }

    /**
     * Import ODX Overall Velocity as a differential import.
     *
     * Identity:
     * equipment_inspection_id + measurement_point_id + measurement_datetime
     *
     * Existing identity:
     * - is checked against the database
     * - is NOT inserted again
     * - is NOT updated
     *
     * New identity:
     * - is inserted once
     *
     * Exact duplicate inside the ODX is deduplicated.
     * Same identity with different values is rejected.
     */
    public function import(string $filePath): array
    {
        if (!is_file($filePath)) {
            throw new RuntimeException(
                'File ODX tidak ditemukan atau tidak dapat dibaca.'
            );
        }

        $rows = $this->parser->parseOverallVelocity($filePath);
        $parserCount = count($rows);

        if ($parserCount === 0) {
            return [
                'message' =>
                    'Tidak ada data Overall Velocity yang ditemukan dari file ODX.',
                'imported' => 0,
                'updated' => 0,
                'existing' => 0,
                'total' => 0,
                'parser_count' => 0,
                'verified_count' => 0,
                'new_count' => 0,
            ];
        }

        Log::info('ODX parser completed.', [
            'file' => basename($filePath),
            'parser_count' => $parserCount,
        ]);

        return DB::transaction(function () use (
            $rows,
            $parserCount
        ) {
            $inspectionCache = [];
            $equipmentCache = [];
            $equipmentInspectionCache = [];
            $measurementPointCache = [];

            $measurementRows = [];
            $equipmentInspectionIds = [];

            foreach ($rows as $index => $data) {

                $rowNumber = $index + 1;

                $equipmentName = trim(
                    (string) ($data['equipment_name'] ?? '')
                );

                $pointName = trim(
                    (string) ($data['measurement_point_name'] ?? '')
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

                try {
                    $measurementCarbon =
                        Carbon::parse($rawDatetime);
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
                 * PATH
                 */
                $pathInfo =
                    $this->parsePath(
                        (string) ($data['path'] ?? '')
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
                    throw new RuntimeException(
                        "ODX row #{$rowNumber}: " .
                        "Equipment ID tidak ditemukan dari path."
                    );
                }

                /*
                 * OVERALL VELOCITY
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
                 * INSPECTION
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
                 * EQUIPMENT
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
                            $equipment->machine_type !==
                            $machineType
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
                 * EQUIPMENT INSPECTION
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
                 * MEASUREMENT POINT
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
                 * PREPARE MEASUREMENT
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
             * PREPARE COUNT
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
             * DEDUPLICATE INSIDE ODX
             */
            $uniqueMeasurementRows = [];

            foreach (
                $measurementRows as $row
            ) {
                $key =
                    $this->measurementKey(
                        $row
                    );

                if (
                    isset(
                        $uniqueMeasurementRows[$key]
                    )
                ) {
                    if (
                        !$this->sameMeasurementValues(
                            $uniqueMeasurementRows[$key],
                            $row
                        )
                    ) {
                        throw new RuntimeException(
                            'ODX integrity gagal: ' .
                            'duplicate measurement dengan nilai berbeda ditemukan: ' .
                            $key
                        );
                    }

                    continue;
                }

                $uniqueMeasurementRows[$key] =
                    $row;
            }

            $uniqueMeasurementRows =
                array_values(
                    $uniqueMeasurementRows
                );

            $expectedCount =
                count(
                    $uniqueMeasurementRows
                );

            if (
                $expectedCount === 0
            ) {
                throw new RuntimeException(
                    'ODX integrity gagal: ' .
                    'tidak ada measurement valid yang berhasil diproses.'
                );
            }

            /*
             * COMPARE DENGAN DATABASE
             */
            $existingRowsByKey = [];

            foreach (
                $equipmentInspectionIds
                as $equipmentInspectionId => $_
            ) {
                $expectedForInspection =
                    array_values(
                        array_filter(
                            $uniqueMeasurementRows,
                            fn (array $row) =>
                                (int) $row[
                                    'equipment_inspection_id'
                                ] ===
                                (int) $equipmentInspectionId
                        )
                    );

                if (
                    !$expectedForInspection
                ) {
                    continue;
                }

                $datetimes =
                    array_values(
                        array_unique(
                            array_map(
                                fn (array $row) =>
                                    $row[
                                        'measurement_datetime'
                                    ],
                                $expectedForInspection
                            )
                        )
                    );

                $existingRows =
                    DB::table(
                        'measurement_results'
                    )
                    ->select([
                        'equipment_inspection_id',
                        'measurement_point_id',
                        'measurement_datetime',
                        'overall_velocity',
                        'unit',
                        'peak_value',
                        'crest_factor',
                    ])
                    ->where(
                        'equipment_inspection_id',
                        (int) $equipmentInspectionId
                    )
                    ->whereIn(
                        'measurement_datetime',
                        $datetimes
                    )
                    ->get();

                foreach (
                    $existingRows as $existing
                ) {
                    $key =
                        (int) $existing->equipment_inspection_id .
                        '|' .
                        (int) $existing->measurement_point_id .
                        '|' .
                        (string) $existing->measurement_datetime;

                    $existingRowsByKey[$key] =
                        $existing;
                }
            }

            /*
             * SEPARATE EXISTING VS NEW
             */
            $newMeasurementRows = [];

            $existingCount = 0;

            foreach (
                $uniqueMeasurementRows as $row
            ) {
                $key =
                    $this->measurementKey(
                        $row
                    );

                if (
                    isset(
                        $existingRowsByKey[$key]
                    )
                ) {
                    $existingCount++;

                    /*
                     * Existing measurement harus
                     * identik dengan ODX.
                     */
                    if (
                        !$this->sameMeasurementValues(
                            $existingRowsByKey[$key],
                            $row
                        )
                    ) {
                        throw new RuntimeException(
                            'ODX integrity gagal: measurement dengan identity yang sama sudah ada di database tetapi nilainya berbeda: ' .
                            $key
                        );
                    }

                    /*
                     * SKIP.
                     */
                    continue;
                }

                /*
                 * BENAR-BENAR BARU.
                 */
                $newMeasurementRows[] =
                    $row;
            }

            /*
             * INSERT ONLY NEW
             */
            foreach (
                array_chunk(
                    $newMeasurementRows,
                    self::BATCH_SIZE
                ) as $chunk
            ) {
                if ($chunk === []) {
                    continue;
                }

                DB::table(
                    'measurement_results'
                )->insert(
                    $chunk
                );
            }

            $newCount =
                count(
                    $newMeasurementRows
                );

            /*
             * VERIFY
             */
            $verifiedCount = 0;

            foreach (
                $equipmentInspectionIds
                as $equipmentInspectionId => $_
            ) {
                $expectedForInspection =
                    array_values(
                        array_filter(
                            $uniqueMeasurementRows,
                            fn (array $row) =>
                                (int) $row[
                                    'equipment_inspection_id'
                                ] ===
                                (int) $equipmentInspectionId
                        )
                    );

                if (
                    !$expectedForInspection
                ) {
                    continue;
                }

                $expectedKeys = [];
                $datetimes = [];

                foreach (
                    $expectedForInspection as $row
                ) {
                    $key =
                        $this->measurementKey(
                            $row
                        );

                    $expectedKeys[$key] =
                        true;

                    $datetimes[
                        $row[
                            'measurement_datetime'
                        ]
                    ] = true;
                }

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
                        (int) $equipmentInspectionId
                    )
                    ->whereIn(
                        'measurement_datetime',
                        array_keys(
                            $datetimes
                        )
                    )
                    ->get();

                $actualKeys = [];

                foreach (
                    $actualRows as $actual
                ) {
                    $key =
                        (int) $actual->equipment_inspection_id .
                        '|' .
                        (int) $actual->measurement_point_id .
                        '|' .
                        (string) $actual->measurement_datetime;

                    if (
                        isset(
                            $expectedKeys[$key]
                        )
                    ) {
                        $actualKeys[$key] =
                            true;
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
                    count(
                        $expectedKeys
                    );
            }

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
             * REFRESH SUMMARY
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
             * DATABASE TOTAL
             */
            $databaseTotal =
                DB::table(
                    'measurement_results'
                )->count();

            /*
             * FINAL LOG
             */
            Log::info(
                'ODX differential import completed.',
                [
                    'parser_count' =>
                        $parserCount,

                    'unique_odx_count' =>
                        $expectedCount,

                    'existing_count' =>
                        $existingCount,

                    'new_count' =>
                        $newCount,

                    'verified_count' =>
                        $verifiedCount,

                    'database_total' =>
                        $databaseTotal,

                    'equipment_inspections' =>
                        count(
                            $equipmentInspectionIds
                        ),
                ]
            );

            return [

                'message' =>
                    'Import ODX selesai. ' .
                    'Parsed: ' .
                    $parserCount .
                    ' | Existing: ' .
                    $existingCount .
                    ' | New: ' .
                    $newCount .
                    ' | DB total: ' .
                    $databaseTotal .
                    '.',

                /*
                 * Sekarang imported benar-benar berarti
                 * measurement BARU yang masuk DB.
                 */
                'imported' =>
                    $newCount,

                /*
                 * Tidak ada update terhadap existing.
                 */
                'updated' =>
                    0,

                'existing' =>
                    $existingCount,

                /*
                 * Total measurement di DB.
                 */
                'total' =>
                    $databaseTotal,

                'parser_count' =>
                    $parserCount,

                'verified_count' =>
                    $verifiedCount,

                'new_count' =>
                    $newCount,
            ];
        }, 3);
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
    | COMPARE MEASUREMENT VALUE
    |--------------------------------------------------------------------------
    */

    private function sameMeasurementValues(
        $existing,
        array $incoming
    ): bool {
        return
            (float) (
                $existing->overall_velocity ??
                $existing['overall_velocity'] ??
                0
            )
            ===
            (float) (
                $incoming['overall_velocity'] ??
                0
            )
            &&
            (string) (
                $existing->unit ??
                $existing['unit'] ??
                ''
            )
            ===
            (string) (
                $incoming['unit'] ??
                ''
            )
            &&
            $this->sameNullableNumber(
                $existing->peak_value ??
                $existing['peak_value'] ??
                null,

                $incoming['peak_value'] ??
                null
            )
            &&
            $this->sameNullableNumber(
                $existing->crest_factor ??
                $existing['crest_factor'] ??
                null,

                $incoming['crest_factor'] ??
                null
            );
    }

    private function sameNullableNumber(
        $a,
        $b
    ): bool {
        if (
            $a === null &&
            $b === null
        ) {
            return true;
        }

        if (
            $a === null ||
            $b === null
        ) {
            return false;
        }

        return
            (float) $a ===
            (float) $b;
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
            substr(
                $pointName,
                -1
            )
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

        if (
            !$highestMeasurement
        ) {
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