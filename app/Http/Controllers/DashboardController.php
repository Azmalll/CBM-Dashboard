<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\EquipmentInspection;
use App\Models\Inspection;
use App\Models\MeasurementResult;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display CBM Dashboard.
     */
    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | BASIC KPI
        |--------------------------------------------------------------------------
        */

        $totalEquipment = Equipment::count();

        $totalInspection = Inspection::count();

        $totalMeasurements = MeasurementResult::count();


        /*
        |--------------------------------------------------------------------------
        | ANALYSIS STATUS
        |--------------------------------------------------------------------------
        */

        $pendingCount =
            EquipmentInspection::whereNull('severity')
                ->orWhere('severity', '')
                ->count();


        /*
        |--------------------------------------------------------------------------
        | CONDITION DISTRIBUTION
        |--------------------------------------------------------------------------
        */

        $normalCount =
            EquipmentInspection::where(
                'severity',
                'Normal'
            )->count();

        $alertCount =
            EquipmentInspection::where(
                'severity',
                'Alert'
            )->count();

        $dangerCount =
            EquipmentInspection::whereIn(
                'severity',
                [
                    'Danger',
                    'Alarm',
                    'Critical',
                    'Fault'
                ]
            )->count();


        /*
        |--------------------------------------------------------------------------
        | EQUIPMENT LIST
        |--------------------------------------------------------------------------
        */

        $equipments =
            Equipment::orderBy('equipment_name')
                ->get();


        /*
        |--------------------------------------------------------------------------
        | SELECTED EQUIPMENT
        |--------------------------------------------------------------------------
        */

        $selectedEquipmentId =
            $request->route('equipment_id')
            ?? $request->get('equipment_id');

        if (
            !$selectedEquipmentId &&
            $equipments->count()
        ) {
            $selectedEquipmentId =
                $equipments->first()->id;
        }


        /*
        |--------------------------------------------------------------------------
        | MEASUREMENT POINT LIST
        |--------------------------------------------------------------------------
        */

        $measurementPoints = collect();

        if ($selectedEquipmentId) {

            $measurementPoints =
                MeasurementResult::with([
                    'measurementPoint'
                ])
                ->whereHas(
                    'equipmentInspection',
                    function ($query) use (
                        $selectedEquipmentId
                    ) {
                        $query->where(
                            'equipment_id',
                            $selectedEquipmentId
                        );
                    }
                )
                ->get()
                ->pluck('measurementPoint')
                ->filter()
                ->unique('id')
                ->sortBy('point_name')
                ->values();
        }


        /*
        |--------------------------------------------------------------------------
        | SELECTED MEASUREMENT POINT
        |--------------------------------------------------------------------------
        */

        $selectedPointId =
            $request->route('measurement_point_id')
            ?? $request->get('measurement_point_id');

        if (
            !$selectedPointId &&
            $measurementPoints->count()
        ) {
            $selectedPointId =
                $measurementPoints->first()->id;
        }


        /*
        |--------------------------------------------------------------------------
        | TREND DATA
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | Semua perhitungan trend menggunakan
        | measurement_datetime.
        |
        | Current  = timestamp paling baru
        | Previous = timestamp tepat sebelumnya
        |
        */

       $trendResults = collect();

if (
    $selectedEquipmentId &&
    $selectedPointId
) {

    $trendResults =
        MeasurementResult::with([
            'equipmentInspection.inspection',
            'measurementPoint',
        ])
        ->where(
            'measurement_point_id',
            $selectedPointId
        )
        ->whereHas(
            'equipmentInspection',
            function ($query) use (
                $selectedEquipmentId
            ) {
                $query->where(
                    'equipment_id',
                    $selectedEquipmentId
                );
            }
        )
        ->get()
        ->sortBy(function ($result) {

            return
                $result->measurement_datetime
                ?? optional(
                    $result->equipmentInspection?->inspection
                )->inspection_date
                ?? $result->created_at;

        })
        ->values();
}


        /*
        |--------------------------------------------------------------------------
        | TREND LABELS / VALUES / TIMESTAMPS
        |--------------------------------------------------------------------------
        */

        $trendLabels = [];

        $trendValues = [];

        $trendTimestamps = [];


        foreach ($trendResults as $result) {

            $measurementDatetime =
                $result->measurement_datetime;


            /*
            |--------------------------------------------------------------------------
            | X-AXIS LABEL
            |--------------------------------------------------------------------------
            |
            | Tanggal + jam measurement.
            |
            | Contoh:
            | 04 Aug 14:27
            |
            */

            $trendLabels[] =
                $measurementDatetime
                    ? date(
                        'd M H:i',
                        strtotime(
                            $measurementDatetime
                        )
                    )
                    : '-';


            /*
            |--------------------------------------------------------------------------
            | OVERALL VALUE
            |--------------------------------------------------------------------------
            */

            $trendValues[] =
                (float) $result->overall_velocity;


            /*
            |--------------------------------------------------------------------------
            | FULL TIMESTAMP UNTUK TOOLTIP
            |--------------------------------------------------------------------------
            */

            $trendTimestamps[] =
                $measurementDatetime
                    ? date(
                        'Y-m-d H:i:s',
                        strtotime(
                            $measurementDatetime
                        )
                    )
                    : '';
        }


        /*
        |--------------------------------------------------------------------------
        | CURRENT / PREVIOUS
        |--------------------------------------------------------------------------
        |
        | Current:
        | measurement_datetime paling baru.
        |
        | Previous:
        | measurement_datetime tepat sebelumnya.
        |
        */

        $currentTrendResult =
            $trendResults->last();

        $previousTrendResult =
            $trendResults->count() > 1
                ? $trendResults->get(
                    $trendResults->count() - 2
                )
                : null;


        /*
        |--------------------------------------------------------------------------
        | CURRENT VALUE
        |--------------------------------------------------------------------------
        */

        $currentTrendValue =
            $currentTrendResult
                ? (float)
                    $currentTrendResult->overall_velocity
                : null;


        /*
        |--------------------------------------------------------------------------
        | PREVIOUS VALUE
        |--------------------------------------------------------------------------
        */

        $previousTrendValue =
            $previousTrendResult
                ? (float)
                    $previousTrendResult->overall_velocity
                : null;


        /*
        |--------------------------------------------------------------------------
        | CHANGE ABSOLUTE
        |--------------------------------------------------------------------------
        */

        $trendChange =
            (
                $currentTrendValue !== null &&
                $previousTrendValue !== null
            )
                ? $currentTrendValue -
                    $previousTrendValue
                : null;


        /*
        |--------------------------------------------------------------------------
        | CHANGE PERCENTAGE
        |--------------------------------------------------------------------------
        |
        | Formula:
        |
        | ((Current - Previous) / Previous) x 100
        |
        */

        $trendChangePercent = null;

        if (
            $currentTrendValue !== null &&
            $previousTrendValue !== null &&
            $previousTrendValue != 0
        ) {

            $trendChangePercent =
                (
                    (
                        $currentTrendValue -
                        $previousTrendValue
                    )
                    /
                    $previousTrendValue
                )
                * 100;
        }


        /*
        |--------------------------------------------------------------------------
        | TREND STATUS
        |--------------------------------------------------------------------------
        |
        | Current > Previous = Increasing
        | Current = Previous = Stable
        | Current < Previous = Improving
        |
        */

        $trendStatus = null;

        if (
            $currentTrendValue !== null &&
            $previousTrendValue !== null
        ) {

            if (
                $currentTrendValue >
                $previousTrendValue
            ) {

                $trendStatus = 'Increasing';

            } elseif (
                $currentTrendValue <
                $previousTrendValue
            ) {

                $trendStatus = 'Improving';

            } else {

                $trendStatus = 'Stable';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | CURRENT / PREVIOUS TIMESTAMP
        |--------------------------------------------------------------------------
        */

        $currentTrendTimestamp =
            $currentTrendResult
                ? $currentTrendResult->measurement_datetime
                : null;

        $previousTrendTimestamp =
            $previousTrendResult
                ? $previousTrendResult->measurement_datetime
                : null;


        /*
        |--------------------------------------------------------------------------
        | SELECTED EQUIPMENT NAME
        |--------------------------------------------------------------------------
        */

        $selectedEquipment =
            $equipments->firstWhere(
                'id',
                $selectedEquipmentId
            );


        /*
        |--------------------------------------------------------------------------
        | SELECTED POINT NAME
        |--------------------------------------------------------------------------
        */

        $selectedPoint =
            $measurementPoints->firstWhere(
                'id',
                $selectedPointId
            );


        /*
        |--------------------------------------------------------------------------
        | LATEST INSPECTION SESSION / HIGHEST VIBRATION
        |--------------------------------------------------------------------------
        |
        | Dashboard equipment condition MUST be derived from the latest
        | inspection session of each equipment, not from the latest timestamp
        | across individual measurement points.
        |
        | Flow:
        | Equipment
        |   -> latest EquipmentInspection
        |   -> all MeasurementResult in that session
        |   -> MAX overall_velocity
        |   -> matching measurement point
        |
        */

        $equipmentInspectionHistory =
            EquipmentInspection::with([
                'equipment',
                'inspection',
                'measurementResults.measurementPoint',
            ])
                ->whereIn(
                    'equipment_id',
                    $equipments->pluck('id')
                )
                ->get()
                ->filter(function ($inspection) {
                    return $inspection->equipment_id !== null;
                })
                ->groupBy('equipment_id');


        /*
        |--------------------------------------------------------------------------
        | LATEST INSPECTION PER EQUIPMENT
        |--------------------------------------------------------------------------
        |
        | Use inspection date first, then id as the tie breaker.
        |
        */

        $latestInspectionsPerEquipment =
            $equipmentInspectionHistory
                ->map(function ($inspections) {
                    return $inspections
                        ->sort(function ($a, $b) {
                            $dateA = optional($a->inspection)->inspection_date
                                ?? $a->created_at;
                            $dateB = optional($b->inspection)->inspection_date
                                ?? $b->created_at;

                            $timestampA = strtotime((string) $dateA);
                            $timestampB = strtotime((string) $dateB);

                            if ($timestampA === $timestampB) {
                                return $b->id <=> $a->id;
                            }

                            return $timestampB <=> $timestampA;
                        })
                        ->first();
                });


        /*
        |--------------------------------------------------------------------------
        | LATEST HIGHEST MEASUREMENT PER EQUIPMENT
        |--------------------------------------------------------------------------
        */

        $latestMeasurementsPerEquipment =
            $latestInspectionsPerEquipment
                ->map(function ($equipmentInspection) {
                    return $equipmentInspection
                        ->measurementResults
                        ->filter(function ($measurement) {
                            return $measurement->overall_velocity !== null;
                        })
                        ->sortByDesc(function ($measurement) {
                            return (float) $measurement->overall_velocity;
                        })
                        ->first();
                })
                ->filter();


        /*
        |--------------------------------------------------------------------------
        | GLOBAL HIGHEST VIBRATION
        |--------------------------------------------------------------------------
        */

        $highestVibration =
            $latestMeasurementsPerEquipment
                ->sortByDesc(function ($measurement) {
                    return (float) $measurement->overall_velocity;
                })
                ->first();


        /*
        |--------------------------------------------------------------------------
        | EQUIPMENT CONDITION BY AREA + SEVERITY RECURRENCE
        |--------------------------------------------------------------------------
        |
        | Severity is derived from the highest RMS inside the inspection
        | session. This keeps the equipment-level condition consistent with
        | the measurement point that actually produced the highest RMS.
        |
        | Threshold mirror currently used by the ODX importer:
        | < 2.80 = Normal
        | < 4.50 = Alert
        | < 7.10 = Danger
        | >= 7.10 = Critical
        |
        */

        $determineSeverity = static function ($overall): string {
            $overall = (float) $overall;

            if ($overall < 2.80) {
                return 'Normal';
            }

            if ($overall < 4.50) {
                return 'Alert';
            }

            if ($overall < 7.10) {
                return 'Danger';
            }

            return 'Critical';
        };


        $equipmentConditions =
            $equipments
                ->map(
                    function ($equipment)
                    use (
                        $latestMeasurementsPerEquipment,
                        $latestInspectionsPerEquipment,
                        $equipmentInspectionHistory,
                        $determineSeverity
                    ) {

                        $latestMeasurement =
                            $latestMeasurementsPerEquipment
                                ->get($equipment->id);


                        $latestInspection =
                            $latestInspectionsPerEquipment
                                ->get($equipment->id);


                        $latestRms =
                            $latestMeasurement?->overall_velocity;


                        $severity =
                            $latestRms !== null
                                ? $determineSeverity($latestRms)
                                : ($latestInspection?->severity ?: 'Pending');


                        /*
                        |--------------------------------------------------------------------------
                        | SEVERITY RECURRENCE
                        |--------------------------------------------------------------------------
                        |
                        | Recalculate the historical session severity from the
                        | highest measurement in each session so stale
                        | equipment_inspections.severity values do not distort
                        | the dashboard summary.
                        |
                        */

                        $severityRecurrenceCount = 0;

                        if ($severity !== 'Pending') {
                            $severityRecurrenceCount =
                                $equipmentInspectionHistory
                                    ->get(
                                        $equipment->id,
                                        collect()
                                    )
                                    ->filter(function ($inspection) use (
                                        $severity,
                                        $determineSeverity
                                    ) {
                                        $highest = $inspection
                                            ->measurementResults
                                            ->filter(function ($measurement) {
                                                return $measurement->overall_velocity !== null;
                                            })
                                            ->max(function ($measurement) {
                                                return (float) $measurement->overall_velocity;
                                            });

                                        if ($highest === null) {
                                            $inspectionSeverity =
                                                $inspection->severity ?? 'Pending';

                                            return $inspectionSeverity === $severity;
                                        }

                                        return $determineSeverity($highest) === $severity;
                                    })
                                    ->count();
                        }


                        $area =
                            trim(
                                (string) (
                                    $equipment->area ?? ''
                                )
                            );


                        return [
                            'equipment' =>
                                $equipment,

                            'area' =>
                                $area !== ''
                                    ? $area
                                    : 'Unassigned Area',

                            'measurement' =>
                                $latestMeasurement,

                            'inspection' =>
                                $latestInspection,

                            'severity' =>
                                $severity,

                            'severity_recurrence_count' =>
                                $severityRecurrenceCount,

                            'overall_velocity' =>
                                $latestMeasurement?->overall_velocity,

                            'measurement_datetime' =>
                                $latestMeasurement?->measurement_datetime,

                            'measurement_point' =>
                                $latestMeasurement?->measurementPoint,
                        ];
                    }
                )
                ->groupBy('area')
                ->sortKeys();


        /*
        |--------------------------------------------------------------------------
        | RETURN DASHBOARD
        |--------------------------------------------------------------------------
        */
        /*
        |--------------------------------------------------------------------------
        | RETURN DASHBOARD
        |--------------------------------------------------------------------------
        */

        return view(
            'dashboard.index',
            compact(
                'totalEquipment',
                'totalInspection',
                'totalMeasurements',

                'pendingCount',
                'normalCount',
                'alertCount',
                'dangerCount',

                'equipments',
                'measurementPoints',

                'selectedEquipmentId',
                'selectedPointId',

                'selectedEquipment',
                'selectedPoint',

                'trendLabels',
                'trendValues',
                'trendTimestamps',

                'currentTrendValue',
                'previousTrendValue',

                'trendChange',
                'trendChangePercent',
                'trendStatus',

                'currentTrendTimestamp',
                'previousTrendTimestamp',

                'highestVibration',

                'equipmentConditions'
            )
        );
    }
}