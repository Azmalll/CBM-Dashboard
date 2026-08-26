@extends('layouts.app')

@section('title', 'CBM Dashboard')

@section('content')

<div class="space-y-8">

    {{-- ========================================================= --}}
    {{-- HEADER --}}
    {{-- ========================================================= --}}

    <div>
        <h1 class="text-3xl font-bold text-[#0F2D5C]">
            CBM Dashboard
        </h1>

        <p class="text-gray-500 mt-1">
            Condition Based Maintenance Monitoring
        </p>
    </div>


    {{-- ========================================================= --}}
    {{-- KPI --}}
    {{-- ========================================================= --}}

    @php
        /*
        | Equipment status is based on the latest condition of each equipment.
        | This keeps the KPI counts at EQUIPMENT level, not measurement level.
        */
        $latestEquipmentConditions = $equipmentConditions->flatten(1);

        $normalCount = $latestEquipmentConditions
            ->filter(fn ($condition) => ($condition['severity'] ?? null) === 'Normal')
            ->count();

        $alertCount = $latestEquipmentConditions
            ->filter(fn ($condition) => ($condition['severity'] ?? null) === 'Alert')
            ->count();

        $dangerCount = $latestEquipmentConditions
            ->filter(fn ($condition) => in_array(
                ($condition['severity'] ?? null),
                ['Danger', 'Alarm'],
                true
            ))
            ->count();

        $criticalCount = $latestEquipmentConditions
            ->filter(fn ($condition) => in_array(
                ($condition['severity'] ?? null),
                ['Critical', 'Fault'],
                true
            ))
            ->count();

        $equipmentRequiringAttention =
            $alertCount +
            $dangerCount +
            $criticalCount;

        /*
        | Build COMPLETE inspection history for every equipment.
        |
        | Flow:
        | Equipment card
        |   -> Inspection History
        |       -> select one inspection session
        |           -> measurement detail of all points in that session
        |
        | EquipmentInspection is the bridge between an equipment and an
        | inspection session. Each MeasurementResult belongs to an
        | EquipmentInspection, so historical sessions remain separated.
        */
        $equipmentIds = $latestEquipmentConditions
            ->pluck('equipment.id')
            ->filter()
            ->unique()
            ->values();

        $equipmentInspectionHistory = collect();

        if ($equipmentIds->count()) {
            $equipmentInspectionHistory =
                \App\Models\EquipmentInspection::with([
                    'inspection',
                    'equipment',
                    'measurementResults.measurementPoint',
                ])
                ->whereIn('equipment_id', $equipmentIds)
                ->get()
                ->groupBy('equipment_id');
        }

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

        $equipmentDetailData = $latestEquipmentConditions
            ->mapWithKeys(function ($condition) use (
                $equipmentInspectionHistory,
                $determineSeverity
            ) {
                $equipment = $condition['equipment'];

                $history = $equipmentInspectionHistory
                    ->get($equipment->id, collect())
                    ->sort(function ($a, $b) {
                        $dateA = optional($a->inspection)->inspection_date
                            ?? $a->created_at;
                        $dateB = optional($b->inspection)->inspection_date
                            ?? $b->created_at;

                        $timestampA = strtotime((string) $dateA);
                        $timestampB = strtotime((string) $dateB);

                        if ($timestampA === $timestampB) {
                            return $timestampB <=> $timestampA;
                        }

                        return $timestampB <=> $timestampA;
                    })
                    ->values();

                return [
                    (string) $equipment->id => [
                        'name' => $equipment->equipment_name,
                        'area' => $condition['area'],
                        'currentSeverity' => $condition['severity'] ?? 'Pending',
                        'history' => $history->map(function ($equipmentInspection) use (
                            $determineSeverity
                        ) {
                            $inspection = $equipmentInspection->inspection;

                            $measurements = $equipmentInspection->measurementResults
                                ->sortBy(function ($measurement) {
                                    return [
                                        $measurement->measurement_datetime ?? '',
                                        $measurement->measurementPoint?->point_name ?? '',
                                    ];
                                })
                                ->values();

                            /*
                            |--------------------------------------------------------------------------
                            | HIGHEST MEASUREMENT IN THIS SESSION
                            |--------------------------------------------------------------------------
                            |
                            | The session summary must use the highest RMS
                            | measurement result, not the value stored earlier in
                            | equipment_inspections.highest_overall.
                            |
                            */
                            $highestMeasurement = $measurements
                                ->filter(function ($measurement) {
                                    return $measurement->overall_velocity !== null;
                                })
                                ->sortByDesc(function ($measurement) {
                                    return (float) $measurement->overall_velocity;
                                })
                                ->first();

                            $highestOverall = $highestMeasurement?->overall_velocity;

                            $sessionSeverity =
                                $highestOverall !== null
                                    ? $determineSeverity($highestOverall)
                                    : ($equipmentInspection->severity ?? 'Pending');


                            /*
                            |--------------------------------------------------------------------------
                            | INSPECTOR SUMMARY
                            |--------------------------------------------------------------------------
                            |
                            | Inspector is assigned at measurement-result level.
                            | If all assigned measurements have the same inspector,
                            | show that inspector. If they differ, show Multiple.
                            |
                            */
                            $inspectors = $measurements
                                ->map(function ($measurement) {
                                    return trim((string) ($measurement->inspector ?? ''));
                                })
                                ->filter(function ($inspector) {
                                    return $inspector !== ''
                                        && strtolower($inspector) !== 'unassigned';
                                })
                                ->unique()
                                ->values();

                            $inspectorSummary = '-';

                            if ($inspectors->count() === 1) {
                                $inspectorSummary = $inspectors->first();
                            } elseif ($inspectors->count() > 1) {
                                $inspectorSummary = 'Multiple';
                            } elseif (
                                !empty($inspection?->inspector) &&
                                strtolower((string) $inspection->inspector) !== 'unassigned'
                            ) {
                                $inspectorSummary = $inspection->inspector;
                            }

                            return [
                                'id' => $equipmentInspection->id,
                                'inspectionDate' => $inspection?->inspection_date,
                                'inspector' => $inspectorSummary,
                                'remarks' => $inspection?->remarks ?? '',
                                'severity' => $sessionSeverity,
                                'highestOverall' => $highestOverall,
                                'highestPoint' => $highestMeasurement?->measurementPoint?->point_name,
                                'highestDirection' => $highestMeasurement?->measurementPoint?->direction,
                                'diagnosis' => $equipmentInspection->diagnosis ?? '',
                                'recommendation' => $equipmentInspection->recommendation ?? '',
                                'reportFile' => $equipmentInspection->report_file ?? null,

                                'operatingParameters' =>
                                    $equipmentInspection->operating_parameters ?? null,

                                'measurementCount' => $measurements->count(),

                                'measurements' => $measurements->map(function ($measurement) use (
                                    $determineSeverity
                                ) {
                                    $overall = $measurement->overall_velocity;

                                    return [
                                        'point' => $measurement->measurementPoint?->point_name ?? '-',
                                        'direction' => $measurement->measurementPoint?->direction ?? '',
                                        'location' => $measurement->measurementPoint?->location ?? '',
                                        'datetime' => $measurement->measurement_datetime,
                                        'overall' => $overall,
                                        'unit' => $measurement->unit ?? 'mm/s RMS',
                                        'crest' => $measurement->crest_factor,
                                        'severity' => $overall !== null
                                            ? $determineSeverity($overall)
                                            : 'Pending',
                                        'inspector' => $measurement->inspector ?? '',
                                    ];
                                })->all(),
                            ];
                        })->all(),
                    ],
                ];
            })->all();
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">

        {{-- TOTAL EQUIPMENT --}}
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <p class="text-gray-500 text-sm">
                Total Equipment
            </p>

            <p class="text-3xl font-bold text-[#0F2D5C] mt-2">
                {{ $totalEquipment }}
            </p>

            <p class="text-gray-400 text-sm mt-1">
                Registered equipment
            </p>
        </div>


        {{-- INSPECTION SESSIONS --}}
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <p class="text-gray-500 text-sm">
                Inspection Sessions
            </p>

            <p class="text-3xl font-bold text-[#0F2D5C] mt-2">
                {{ $totalInspection }}
            </p>

            <p class="text-gray-400 text-sm mt-1">
                Total inspection sessions
            </p>
        </div>


        {{-- MEASUREMENTS --}}
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <p class="text-gray-500 text-sm">
                Measurements
            </p>

            <p class="text-3xl font-bold text-[#0F2D5C] mt-2">
                {{ $totalMeasurements }}
            </p>

            <p class="text-gray-400 text-sm mt-1">
                Vibration measurements
            </p>
        </div>


        {{-- EQUIPMENT REQUIRING ATTENTION --}}
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <p class="text-gray-500 text-sm">
                Equipment Requiring Attention
            </p>

            <p class="text-3xl font-bold text-red-600 mt-2">
                {{ $equipmentRequiringAttention }}
            </p>

            <p class="text-gray-400 text-sm mt-1">
                Alert + Danger + Critical
            </p>
        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- CONDITION DISTRIBUTION --}}
    {{-- ========================================================= --}}

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">

        {{-- NORMAL --}}
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <div class="flex justify-between items-center">

                <div>
                    <p class="text-gray-500">
                        Normal
                    </p>

                    <p class="text-3xl font-bold text-green-600 mt-2">
                        {{ $normalCount }}
                    </p>
                </div>

                <span class="px-4 py-2 rounded-xl bg-green-100 text-green-700">
                    Normal
                </span>

            </div>
        </div>


        {{-- ALERT --}}
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <div class="flex justify-between items-center">

                <div>
                    <p class="text-gray-500">
                        Alert
                    </p>

                    <p class="text-3xl font-bold text-yellow-600 mt-2">
                        {{ $alertCount }}
                    </p>
                </div>

                <span class="px-4 py-2 rounded-xl bg-yellow-100 text-yellow-700">
                    Alert
                </span>

            </div>
        </div>


        {{-- DANGER --}}
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <div class="flex justify-between items-center">

                <div>
                    <p class="text-gray-500">
                        Danger
                    </p>

                    <p class="text-3xl font-bold text-orange-600 mt-2">
                        {{ $dangerCount }}
                    </p>
                </div>

                <span class="px-4 py-2 rounded-xl bg-orange-100 text-orange-700">
                    Danger
                </span>

            </div>
        </div>


        {{-- CRITICAL --}}
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <div class="flex justify-between items-center">

                <div>
                    <p class="text-gray-500">
                        Critical
                    </p>

                    <p class="text-3xl font-bold text-red-700 mt-2">
                        {{ $criticalCount }}
                    </p>
                </div>

                <span class="px-4 py-2 rounded-xl bg-red-100 text-red-700">
                    Critical
                </span>

            </div>
        </div>

    </div>

{{-- ========================================================= --}}
{{-- VIBRATION TREND --}}
{{-- ========================================================= --}}

<div class="bg-white rounded-2xl shadow-sm p-8">

    <div class="flex flex-col lg:flex-row lg:justify-between lg:items-end gap-5 mb-6">

        <div>
            <h2 class="text-2xl font-bold text-[#0F2D5C]">
                Vibration Trend
            </h2>

            <p class="text-gray-500 mt-1">
                Overall velocity vibration trend
            </p>
        </div>

        <div class="flex flex-col md:flex-row gap-3">

            {{-- EQUIPMENT + MEASUREMENT POINT FILTER --}}
            <form
                method="GET"
                action="{{ route('dashboard') }}"
                class="flex flex-col md:flex-row gap-3"
            >

                {{-- EQUIPMENT --}}
                <select
                    name="equipment_id"
                    onchange="this.form.submit()"
                    class="border border-gray-300 rounded-xl px-4 py-3 min-w-[220px]"
                >

                    @foreach($equipments as $equipment)

                        <option
                            value="{{ $equipment->id }}"
                            {{ (int) $selectedEquipmentId === (int) $equipment->id ? 'selected' : '' }}
                        >
                            {{ $equipment->equipment_name }}
                        </option>

                    @endforeach

                </select>


                {{-- MEASUREMENT POINT --}}
                <select
                    name="measurement_point_id"
                    onchange="this.form.submit()"
                    class="border border-gray-300 rounded-xl px-4 py-3 min-w-[220px]"
                >

                    @foreach($measurementPoints as $point)

                        <option
                            value="{{ $point->id }}"
                            {{ (int) $selectedPointId === (int) $point->id ? 'selected' : '' }}
                        >
                            {{ $point->point_name }}

                            @if($point->direction)
                                - {{ $point->direction }}
                            @endif
                        </option>

                    @endforeach

                </select>

            </form>


            {{-- VIBRATION DISPLAY UNIT --}}
            <div
                class="inline-flex items-center self-start md:self-auto
                       gap-1 border border-gray-300 rounded-xl
                       p-1 bg-gray-50"
            >

                <button
                    type="button"
                    id="unitMmSButton"
                    onclick="setVibrationDisplayUnit('mm/s RMS')"
                    class="px-3 py-2 rounded-lg text-sm font-medium transition"
                >
                    mm/s RMS
                </button>

                <button
                    type="button"
                    id="unitInchSButton"
                    onclick="setVibrationDisplayUnit('inch/s RMS')"
                    class="px-3 py-2 rounded-lg text-sm font-medium transition"
                >
                    inch/s RMS
                </button>

            </div>

        </div>

    </div>


    {{-- SELECTED INFO --}}

    @if($selectedEquipment || $selectedPoint)

        <div class="bg-gray-50 rounded-xl px-5 py-4 mb-6">

            <div class="flex flex-wrap gap-x-10 gap-y-2">

                <div>
                    <p class="text-gray-400 text-sm">
                        Equipment
                    </p>

                    <p class="font-semibold text-[#0F2D5C]">
                        {{ $selectedEquipment?->equipment_name ?? '-' }}
                    </p>
                </div>


                <div>
                    <p class="text-gray-400 text-sm">
                        Measurement Point
                    </p>

                    <p class="font-semibold text-[#0F2D5C]">
                        {{ $selectedPoint?->point_name ?? '-' }}

                        @if($selectedPoint?->direction)
                            - {{ $selectedPoint->direction }}
                        @endif
                    </p>
                </div>

            </div>

        </div>

    @endif


    {{-- ========================================================= --}}
    {{-- CURRENT / PREVIOUS / CHANGE --}}
    {{-- ========================================================= --}}

    @if(count($trendLabels))

        <div class="flex flex-wrap items-end gap-x-10 gap-y-4 mb-6">

            {{-- CURRENT --}}
            <div>

                <p class="text-xs text-gray-400 uppercase tracking-wide">
                    Current
                </p>

                <p
                    id="currentTrendValueDisplay"
                    class="text-lg font-bold text-[#0F2D5C] mt-1"
                    data-value="{{ $currentTrendValue ?? '' }}"
                >
                    -
                </p>

                @if(!empty($currentTrendTimestamp))
    <p class="text-xs text-gray-400 mt-1">
        {{ date('d M Y', strtotime($currentTrendTimestamp)) }}
    </p>
    <p class="text-xs text-gray-400">
        {{ date('H:i:s', strtotime($currentTrendTimestamp)) }}
    </p>
@endif

            </div>


            {{-- PREVIOUS --}}
            <div>

                <p class="text-xs text-gray-400 uppercase tracking-wide">
                    Previous
                </p>

                <p
                    id="previousTrendValueDisplay"
                    class="text-lg font-semibold text-gray-600 mt-1"
                    data-value="{{ $previousTrendValue ?? '' }}"
                >
                    -
                </p>

                @if(!empty($previousTrendTimestamp))
    <p class="text-xs text-gray-400 mt-1">
        {{ date('d M Y', strtotime($previousTrendTimestamp)) }}
    </p>
    <p class="text-xs text-gray-400">
        {{ date('H:i:s', strtotime($previousTrendTimestamp)) }}
    </p>
@endif

            </div>


            {{-- CHANGE --}}
            <div>

                <p class="text-xs text-gray-400 uppercase tracking-wide">
                    Change
                </p>

                @php
                    $displayChangePercent =
                        isset($trendChangePercent)
                            ? $trendChangePercent
                            : null;

                    $changeClass =
                        $displayChangePercent !== null
                            ? (
                                $displayChangePercent > 0
                                    ? 'text-red-600'
                                    : (
                                        $displayChangePercent < 0
                                            ? 'text-green-600'
                                            : 'text-gray-600'
                                    )
                            )
                            : 'text-gray-600';
                @endphp

                <p class="text-lg font-semibold mt-1 {{ $changeClass }}">
                    {{ $displayChangePercent !== null
                        ? (
                            $displayChangePercent > 0 ? '+' : ''
                        ) . number_format((float) $displayChangePercent, 2) . '%'
                        : '-' }}
                </p>

            </div>


            {{-- TREND STATUS --}}
            @if(!empty($trendStatus))

                @php
                    $trendStatusClass = match ($trendStatus) {
                        'Increasing' => 'bg-red-100 text-red-700',
                        'Improving' => 'bg-green-100 text-green-700',
                        'Stable' => 'bg-gray-100 text-gray-600',
                        default => 'bg-gray-100 text-gray-600',
                    };
                @endphp

                <div>

                    <p class="text-xs text-gray-400 uppercase tracking-wide">
                        Trend
                    </p>

                    <span
                        class="inline-flex items-center px-3 py-1.5
                               rounded-full text-xs font-semibold mt-1
                               {{ $trendStatusClass }}"
                    >
                        {{ $trendStatus }}
                    </span>

                </div>

            @endif

        </div>


        {{-- CHART --}}

        <div class="w-full h-[380px]">
            <canvas id="dashboardVibrationTrend"></canvas>
        </div>

    @else

        <div class="bg-gray-50 rounded-xl p-10 text-center text-gray-500">
            No vibration trend data available for the selected measurement point.
        </div>

    @endif

</div>

    {{-- ========================================================= --}}
    {{-- HIGHEST VIBRATION --}}
    {{-- ========================================================= --}}

    <div class="bg-white rounded-2xl shadow-sm p-8">

        <div class="mb-6">

            <h2 class="text-2xl font-bold text-[#0F2D5C]">
                Highest Vibration
            </h2>

            <p class="text-gray-500 mt-1">
                Highest vibration from the latest measurement session of each equipment
            </p>

        </div>


        @if($highestVibration)

            <div class="overflow-x-auto">

                <table class="w-full">

                    <thead class="bg-gray-50">

                        <tr>

                            <th class="px-5 py-4 text-left">
                                Equipment
                            </th>

                            <th class="px-5 py-4 text-left">
                                Overall
                            </th>

                            <th class="px-5 py-4 text-left">
                                Point
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <tr class="border-t">

                            <td class="px-5 py-4 font-semibold">

                                {{ $highestVibration->equipmentInspection?->equipment?->equipment_name ?? '-' }}

                            </td>


                            <td class="px-5 py-4">

                                <span
                                    class="font-bold text-[#0F2D5C]"
                                    data-vibration-value="{{ $highestVibration->overall_velocity }}"
                                    data-vibration-unit="mm/s RMS"
                                >
                                    {{ number_format((float) $highestVibration->overall_velocity, 2) }} mm/s RMS
                                </span>

                            </td>


                            <td class="px-5 py-4">

                                {{ $highestVibration->measurementPoint?->point_name ?? '-' }}

                                @if($highestVibration->measurementPoint?->direction)
                                    - {{ $highestVibration->measurementPoint->direction }}
                                @endif

                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>

        @else

            <div class="bg-gray-50 rounded-xl p-6 text-center text-gray-500">
                No vibration measurement available.
            </div>

        @endif

    </div>



    {{-- ========================================================= --}}
    {{-- EQUIPMENT CONDITION BY AREA --}}
    {{-- ========================================================= --}}

    <div class="mb-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">

    <div>
        <h2 class="text-2xl font-bold text-[#0F2D5C]">
            Equipment Condition by Area
        </h2>

        <p class="text-gray-500 mt-1">
            Latest condition of each equipment grouped by area
        </p>
    </div>

    {{-- COMPARISON BUTTON --}}
    <button
        type="button"
        onclick="openComparisonModal()"
        class="inline-flex items-center justify-center gap-2
               px-4 py-2.5 rounded-xl
               bg-[#0F2D5C] text-white
               hover:bg-blue-900
               transition shadow-sm
               text-sm font-semibold
               whitespace-nowrap"
    >
        <span>⇄</span>
        Compare Measurements
    </button>

</div>


        @if($equipmentConditions->count())

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">

                @foreach($equipmentConditions as $area => $equipmentList)

                    <div class="border border-gray-200 rounded-2xl overflow-hidden bg-white shadow-sm">

                        {{-- AREA HEADER --}}
                        <div class="bg-gray-50 px-5 py-4 border-b border-gray-200">

                            <div class="flex items-center justify-between gap-3">

                                <div>
                                    <h3 class="font-bold text-[#0F2D5C] text-lg">
                                        {{ $area }}
                                    </h3>

                                    <p class="text-gray-500 text-sm mt-1">
                                        {{ $equipmentList->count() }} equipment
                                    </p>
                                </div>

                            </div>

                        </div>


                        {{-- EQUIPMENT LIST --}}
                        <div class="divide-y divide-gray-100">

                            @foreach($equipmentList as $condition)

                                @php
                                    $severity = $condition['severity'] ?? 'Pending';

                                    $severityConfig = match ($severity) {
                                        'Normal' => [
                                            'badge' => 'bg-green-100 text-green-700',
                                            'dot' => 'bg-green-500',
                                        ],
                                        'Alert' => [
                                            'badge' => 'bg-yellow-100 text-yellow-700',
                                            'dot' => 'bg-yellow-500',
                                        ],
                                        'Danger', 'Alarm' => [
                                            'badge' => 'bg-orange-100 text-orange-700',
                                            'dot' => 'bg-orange-500',
                                        ],
                                        'Critical', 'Fault' => [
                                            'badge' => 'bg-red-100 text-red-700',
                                            'dot' => 'bg-red-500',
                                        ],
                                        default => [
                                            'badge' => 'bg-gray-100 text-gray-600',
                                            'dot' => 'bg-gray-400',
                                        ],
                                    };
                                @endphp

                                <button
                                    type="button"
                                    onclick="openEquipmentDetail({{ $condition['equipment']->id }})"
                                    class="w-full px-5 py-4 text-left hover:bg-gray-50 transition cursor-pointer focus:outline-none focus:ring-2 focus:ring-inset focus:ring-[#0F2D5C]"
                                    title="Klik untuk melihat inspection history dan detail semua titik pengukuran"
                                >

                                    <div class="flex items-center justify-between gap-4">

                                        <div class="min-w-0">

                                            <div class="flex items-center gap-2">

                                                <span class="w-2.5 h-2.5 rounded-full shrink-0 {{ $severityConfig['dot'] }}"></span>

                                                <span class="font-semibold text-[#0F2D5C] truncate">
                                                    {{ $condition['equipment']->equipment_name }}
                                                </span>

                                            </div>

                                            @if($condition['overall_velocity'] !== null)
                                                <p class="text-sm text-gray-500 mt-1 ml-4">
                                                    <span
                                                        data-vibration-value="{{ $condition['overall_velocity'] }}"
                                                        data-vibration-unit="mm/s RMS"
                                                    >
                                                        {{ number_format((float) $condition['overall_velocity'], 2) }} mm/s RMS
                                                    </span>

                                                    @if($condition['measurement_point'])
                                                        · {{ $condition['measurement_point']->point_name }}

                                                        @if($condition['measurement_point']->direction)
                                                            - {{ $condition['measurement_point']->direction }}
                                                        @endif
                                                    @endif
                                                </p>
                                            @else
                                                <p class="text-sm text-gray-400 mt-1 ml-4">
                                                    No measurement available
                                                </p>
                                            @endif

                                        </div>


                                        <div class="flex flex-col items-end gap-1 shrink-0">

                                            <span class="px-3 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap {{ $severityConfig['badge'] }}">
                                                {{ $severity }}
                                            </span>

                                            @if(
                                                ($condition['severity_recurrence_count'] ?? 0) > 1 &&
                                                in_array(
                                                    $severity,
                                                    ['Alert', 'Danger', 'Alarm', 'Critical', 'Fault'],
                                                    true
                                                )
                                            )
                                                <span class="text-[11px] text-gray-500 whitespace-nowrap">
                                                    {{ $severity }} × {{ $condition['severity_recurrence_count'] }} sessions
                                                </span>
                                            @endif

                                        </div>

                                    </div>

                                </button>

                            @endforeach

                        </div>

                    </div>

                @endforeach

            </div>

        @else

            <div class="bg-gray-50 rounded-xl p-6 text-center text-gray-500">
                No equipment condition data available.
            </div>

        @endif

    </div>

</div>

{{-- ========================================================= --}}
{{-- MEASUREMENT COMPARISON MODAL --}}
{{-- ========================================================= --}}

<div
    id="comparisonModal"
    class="fixed inset-0 z-[60] hidden"
    aria-hidden="true"
>
    <div
        class="absolute inset-0 bg-black/50"
        onclick="closeComparisonModal()"
    ></div>

    <div class="relative min-h-screen flex items-center justify-center p-4">

        <div
            class="relative bg-white w-full max-w-6xl
                   max-h-[92vh] rounded-2xl shadow-2xl
                   overflow-hidden"
        >

            {{-- HEADER --}}
            <div
                class="px-6 py-5 border-b border-gray-200
                       flex items-start justify-between gap-4"
            >

                <div>

                    <p class="text-xs uppercase tracking-[0.18em]
                              text-gray-400 font-semibold">
                        CBM Analysis
                    </p>

                    <h2 class="text-xl font-bold text-[#0F2D5C] mt-1">
                        Measurement Comparison
                    </h2>

                    <p class="text-sm text-gray-500 mt-1">
                        Compare two inspection sessions from any equipment and date.
                    </p>

                </div>

                <button
                    type="button"
                    onclick="closeComparisonModal()"
                    class="w-9 h-9 rounded-full
                           hover:bg-gray-100 text-gray-500
                           text-xl leading-none"
                >
                    &times;
                </button>

            </div>


            {{-- BODY --}}
            <div
                class="p-6 overflow-y-auto
                       max-h-[calc(92vh-100px)]"
            >

                {{-- ================================================= --}}
                {{-- CASCADE SELECTOR --}}
                {{-- ================================================= --}}

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                    {{-- MEASUREMENT A --}}
                    <div
                        class="bg-gray-50 rounded-xl
                               border border-gray-200 p-5"
                    >

                        <p class="text-xs uppercase tracking-wide
                                  text-gray-400 font-semibold">
                            Measurement A
                        </p>

                        <div class="mt-3">

                            <label
                                class="block text-xs
                                       font-medium text-gray-500 mb-1"
                            >
                                Equipment
                            </label>

                            <select
                                id="comparisonEquipmentA"
                                onchange="updateComparisonDates('A')"
                                class="w-full rounded-lg border
                                       border-gray-300 bg-white
                                       px-3 py-2.5 text-sm
                                       text-[#0F2D5C]"
                            >
                                <option value="">
                                    Select equipment
                                </option>
                            </select>

                        </div>


                        <div class="mt-3">

                            <label
                                class="block text-xs
                                       font-medium text-gray-500 mb-1"
                            >
                                Inspection Date
                            </label>

                            <select
                                id="comparisonDateA"
                                onchange="renderComparison()"
                                disabled
                                class="w-full rounded-lg border
                                       border-gray-300 bg-white
                                       px-3 py-2.5 text-sm
                                       text-[#0F2D5C]
                                       disabled:bg-gray-100
                                       disabled:text-gray-400"
                            >
                                <option value="">
                                    Select inspection date
                                </option>
                            </select>

                        </div>

                    </div>


                    {{-- MEASUREMENT B --}}
                    <div
                        class="bg-gray-50 rounded-xl
                               border border-gray-200 p-5"
                    >

                        <p class="text-xs uppercase tracking-wide
                                  text-gray-400 font-semibold">
                            Measurement B
                        </p>

                        <div class="mt-3">

                            <label
                                class="block text-xs
                                       font-medium text-gray-500 mb-1"
                            >
                                Equipment
                            </label>

                            <select
                                id="comparisonEquipmentB"
                                onchange="updateComparisonDates('B')"
                                class="w-full rounded-lg border
                                       border-gray-300 bg-white
                                       px-3 py-2.5 text-sm
                                       text-[#0F2D5C]"
                            >
                                <option value="">
                                    Select equipment
                                </option>
                            </select>

                        </div>


                        <div class="mt-3">

                            <label
                                class="block text-xs
                                       font-medium text-gray-500 mb-1"
                            >
                                Inspection Date
                            </label>

                            <select
                                id="comparisonDateB"
                                onchange="renderComparison()"
                                disabled
                                class="w-full rounded-lg border
                                       border-gray-300 bg-white
                                       px-3 py-2.5 text-sm
                                       text-[#0F2D5C]
                                       disabled:bg-gray-100
                                       disabled:text-gray-400"
                            >
                                <option value="">
                                    Select inspection date
                                </option>
                            </select>

                        </div>

                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- RESULT --}}
                {{-- ================================================= --}}

                <div
                    id="comparisonResult"
                    class="mt-6"
                >

                    <div
                        class="bg-gray-50 rounded-xl
                               p-8 text-center text-gray-400"
                    >
                        Select Measurement A and Measurement B.
                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

{{-- ========================================================= --}}
{{-- EQUIPMENT MEASUREMENT DETAIL MODAL --}}
{{-- ========================================================= --}}

<div
    id="equipmentDetailModal"
    class="fixed inset-0 z-50 hidden"
    aria-hidden="true"
>

    <div
        class="absolute inset-0 bg-black/50"
        onclick="closeEquipmentDetail()"
    ></div>

    <div class="relative min-h-screen flex items-center justify-center p-4">

        <div class="relative bg-white w-full max-w-5xl max-h-[90vh] rounded-2xl shadow-2xl overflow-hidden">

            {{-- MODAL HEADER --}}
            <div class="px-6 py-5 border-b border-gray-200 flex items-start justify-between gap-4">

                <div>
                    <h2
                        id="equipmentDetailTitle"
                        class="text-xl font-bold text-[#0F2D5C]"
                    >
                        Equipment Detail
                    </h2>

                    <p
                        id="equipmentDetailSubtitle"
                        class="text-sm text-gray-500 mt-1"
                    ></p>
                </div>

                <button
                    type="button"
                    onclick="closeEquipmentDetail()"
                    class="w-9 h-9 rounded-full hover:bg-gray-100 text-gray-500 text-xl leading-none"
                    aria-label="Close"
                >
                    &times;
                </button>

            </div>

            {{-- MODAL BODY --}}
            <div class="p-6 overflow-y-auto max-h-[calc(90vh-90px)]">

                {{-- HISTORY VIEW --}}
                <div id="equipmentHistoryView" class="space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-lg font-bold text-[#0F2D5C]">
                                Inspection History
                            </p>
                            <p class="text-sm text-gray-500">
                                Pilih salah satu inspection session untuk melihat seluruh titik pengukuran.
                            </p>
                        </div>
                    </div>

                    <div id="equipmentHistoryContent" class="space-y-3"></div>
                </div>

                {{-- MEASUREMENT DETAIL VIEW --}}
                <div id="equipmentMeasurementView" class="hidden space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p id="measurementDetailTitle" class="text-lg font-bold text-[#0F2D5C]"></p>
                            <p id="measurementDetailSubtitle" class="text-sm text-gray-500 mt-1"></p>
                        </div>

                        <div class="flex items-center gap-2">
                            @if(auth()->user()?->isAdmin())
                                <input
                                    type="file"
                                    id="analysisReportInput"
                                    accept="application/pdf,.pdf"
                                    class="hidden"
                                    onchange="uploadAnalysisReport(this)"
                                >

                                <input
                                    type="hidden"
                                    id="analysisReportCsrf"
                                    value="{{ csrf_token() }}"
                                >
                            @endif

                            <button
    type="button"
    id="analysisReportButton"
    onclick="handleAnalysisReport()"
    class="px-4 py-2 rounded-lg bg-[#0F2D5C] text-white hover:bg-blue-900 text-sm font-medium"
>
    📄 Analysis Report
</button>

@if(auth()->user()?->isAdmin())
    <button
        type="button"
        id="editAnalysisReportButton"
        onclick="editAnalysisReport()"
        class="hidden px-4 py-2 rounded-lg border border-[#0F2D5C]
               text-[#0F2D5C] hover:bg-blue-50
               text-sm font-medium"
    >
        ✏️ Edit Analysis Report
    </button>
@endif

<button
    type="button"
    onclick="backToEquipmentHistory()"
    class="px-4 py-2 rounded-lg border border-gray-300
           text-gray-700 hover:bg-gray-50
           text-sm font-medium"
>
    ← Back
</button>
                        </div>
                    </div>

                    <div id="measurementSummary" class="grid grid-cols-1 md:grid-cols-4 gap-3"></div>

                    <div id="measurementDetailContent"></div>
                </div>

            </div>

        </div>

    </div>

</div>

<script>

const equipmentDetailData = @json($equipmentDetailData);
const currentUserIsAdmin = @json(auth()->user()?->isAdmin() ?? false);

let activeEquipmentData = null;
let activeHistoryIndex = null;


/*
|--------------------------------------------------------------------------
| MEASUREMENT COMPARISON
|--------------------------------------------------------------------------
|
| Flow:
|
| Measurement A
|   Equipment
|      ↓
|   Inspection Date
|
| Measurement B
|   Equipment
|      ↓
|   Inspection Date
|
| Setelah dua session dipilih:
| - Measurement Detail A & B
| - Current / Previous
| - Delta Value
| - Delta %
| - Severity
| - Measurement Point
| - Timestamp
| - Comparison table seluruh measurement point
| - Comparison chart seluruh measurement point
|
|--------------------------------------------------------------------------
*/


function openComparisonModal() {

    const modal =
        document.getElementById(
            'comparisonModal'
        );

    if (!modal) {
        return;
    }


    const equipmentA =
        document.getElementById(
            'comparisonEquipmentA'
        );

    const equipmentB =
        document.getElementById(
            'comparisonEquipmentB'
        );


    const equipmentOptions =
        Object.entries(
            equipmentDetailData || {}
        )
        .sort(function (a, b) {

            return String(
                a[1]?.name || ''
            ).localeCompare(
                String(
                    b[1]?.name || ''
                )
            );

        })
        .map(function ([equipmentId, equipment]) {

            return `
                <option value="${escapeHtml(equipmentId)}">
                    ${escapeHtml(
                        equipment?.name || '-'
                    )}
                </option>
            `;

        })
        .join('');


    if (equipmentA) {

        equipmentA.innerHTML =
            `
                <option value="">
                    Select equipment
                </option>
            ` +
            equipmentOptions;

        equipmentA.value = '';

    }


    if (equipmentB) {

        equipmentB.innerHTML =
            `
                <option value="">
                    Select equipment
                </option>
            ` +
            equipmentOptions;

        equipmentB.value = '';

    }


    resetComparisonDateSelector('A');
    resetComparisonDateSelector('B');


    const result =
        document.getElementById(
            'comparisonResult'
        );


    if (result) {

        result.innerHTML = `
            <div
                class="bg-gray-50 rounded-xl
                       p-8 text-center text-gray-400"
            >
                Select Measurement A and Measurement B.
            </div>
        `;

    }


    modal.classList.remove('hidden');

    modal.setAttribute(
        'aria-hidden',
        'false'
    );

    document.body.classList.add(
        'overflow-hidden'
    );

}


function closeComparisonModal() {

    const modal =
        document.getElementById(
            'comparisonModal'
        );

    if (!modal) {
        return;
    }


    modal.classList.add('hidden');

    modal.setAttribute(
        'aria-hidden',
        'true'
    );

    document.body.classList.remove(
        'overflow-hidden'
    );

}


/*
|--------------------------------------------------------------------------
| RESET DATE SELECTOR
|--------------------------------------------------------------------------
*/

function resetComparisonDateSelector(side) {

    const select =
        document.getElementById(
            `comparisonDate${side}`
        );

    if (!select) {
        return;
    }


    select.innerHTML = `
        <option value="">
            Select inspection date
        </option>
    `;

    select.value = '';

    select.disabled = true;

}


/*
|--------------------------------------------------------------------------
| CASCADE EQUIPMENT → INSPECTION DATE
|--------------------------------------------------------------------------
*/

function updateComparisonDates(side) {

    const equipmentSelect =
        document.getElementById(
            `comparisonEquipment${side}`
        );

    const dateSelect =
        document.getElementById(
            `comparisonDate${side}`
        );


    if (!equipmentSelect || !dateSelect) {
        return;
    }


    const equipmentId =
        equipmentSelect.value;


    resetComparisonDateSelector(side);


    if (!equipmentId) {

        renderComparison();

        return;

    }


    const equipment =
        equipmentDetailData[
            String(equipmentId)
        ];


    if (!equipment) {

        renderComparison();

        return;

    }


    const history =
        Array.isArray(
            equipment.history
        )
            ? equipment.history
            : [];


    /*
    |--------------------------------------------------------------------------
    | BUILD INSPECTION DATE OPTIONS
    |--------------------------------------------------------------------------
    */

    dateSelect.innerHTML = `
        <option value="">
            Select inspection date
        </option>
    `;


    history.forEach(
        function (session, index) {

            const date =
                formatDateOnly(
                    session?.inspectionDate
                );


            const option =
                document.createElement(
                    'option'
                );


            option.value =
                String(index);

            option.textContent =
                date;


            dateSelect.appendChild(
                option
            );

        }
    );


    dateSelect.disabled =
        history.length === 0;


    renderComparison();

}


/*
|--------------------------------------------------------------------------
| GET SELECTED SESSION
|--------------------------------------------------------------------------
*/

function getSelectedComparisonSession(side) {

    const equipmentSelect =
        document.getElementById(
            `comparisonEquipment${side}`
        );

    const dateSelect =
        document.getElementById(
            `comparisonDate${side}`
        );


    if (
        !equipmentSelect ||
        !dateSelect ||
        !equipmentSelect.value ||
        dateSelect.value === ''
    ) {
        return null;
    }


    const equipment =
        equipmentDetailData[
            String(
                equipmentSelect.value
            )
        ];


    if (!equipment) {
        return null;
    }


    const historyIndex =
        Number(
            dateSelect.value
        );


    const session =
        Array.isArray(
            equipment.history
        )
            ? equipment.history[
                historyIndex
            ]
            : null;


    if (!session) {
        return null;
    }


    return {

        equipmentId:
            String(
                equipmentSelect.value
            ),

        equipmentName:
            equipment.name || '-',

        area:
            equipment.area || '-',

        historyIndex:
            historyIndex,

        session:
            session

    };

}


/*
|--------------------------------------------------------------------------
| COMPARISON HELPERS
|--------------------------------------------------------------------------
*/

function getMeasurementKey(measurement) {

    return [
        measurement?.point || '-',
        measurement?.direction || '-'
    ].join('|');

}


function getMeasurementMap(session) {

    const measurements =
        Array.isArray(
            session?.measurements
        )
            ? session.measurements
            : [];


    const map =
        new Map();


    measurements.forEach(
        function (measurement) {

            map.set(
                getMeasurementKey(
                    measurement
                ),
                measurement
            );

        }
    );


    return map;

}


function getDeltaPercent(valueA, valueB) {

    const a = Number(valueA);
    const b = Number(valueB);


    if (
        !Number.isFinite(a) ||
        !Number.isFinite(b)
    ) {
        return null;
    }


    if (a === 0) {

        if (b === 0) {
            return 0;
        }

        return null;

    }


    return (
        ((b - a) / a) *
        100
    );

}


function formatDeltaPercent(value) {

    if (
        value === null ||
        value === undefined ||
        !Number.isFinite(
            Number(value)
        )
    ) {
        return '-';
    }


    const number =
        Number(value);


    return (
        number > 0
            ? '+'
            : ''
    ) +
    number.toFixed(1) +
    '%';

}


function deltaClass(value) {

    const number =
        Number(value);


    if (!Number.isFinite(number)) {
        return 'text-gray-500';
    }


    /*
    | Positive vibration change = deterioration
    | Negative vibration change = improvement
    */

    if (number > 0) {
        return 'text-red-600';
    }


    if (number < 0) {
        return 'text-green-600';
    }


    return 'text-gray-500';

}


function formatComparisonValue(
    measurement
) {

    if (!measurement) {
        return '-';
    }


    return formatVibrationValue(
        measurement.overall,
        measurement.unit ||
        'mm/s RMS'
    );

}


/*
|--------------------------------------------------------------------------
| MEASUREMENT DETAIL CARD
|--------------------------------------------------------------------------
*/

function renderComparisonDetailCard(
    label,
    item,
    session
) {

    if (!item) {

        return `
            <div
                class="bg-gray-50 rounded-xl
                       border border-dashed
                       border-gray-300 p-5"
            >

                <p class="text-xs uppercase
                          tracking-wide
                          text-gray-400
                          font-semibold">
                    ${escapeHtml(label)}
                </p>

                <p class="text-sm text-gray-400 mt-4">
                    Measurement data unavailable.
                </p>

            </div>
        `;

    }


    const value =
        Number(
            item.overall
        );


    const previous =
        item.previousOverall !== undefined
            ? Number(
                item.previousOverall
            )
            : null;


    const timestamp =
        item.datetime ||
        session?.inspectionDate ||
        null;


    const delta =
        previous !== null &&
        Number.isFinite(previous) &&
        Number.isFinite(value)
            ? value - previous
            : null;


    const deltaPercent =
        previous !== null &&
        Number.isFinite(previous)
            ? getDeltaPercent(
                previous,
                value
            )
            : null;


    return `

        <div
            class="bg-white rounded-xl
                   border border-gray-200
                   p-5"
        >

            <div class="flex items-start
                        justify-between gap-3">

                <div>

                    <p class="text-xs uppercase
                              tracking-wide
                              text-gray-400
                              font-semibold">
                        ${escapeHtml(label)}
                    </p>

                    <p class="text-lg font-bold
                              text-[#0F2D5C] mt-1">
                        ${escapeHtml(
                            session?.equipmentName ||
                            '-'
                        )}
                    </p>

                    <p class="text-xs text-gray-500 mt-1">
                        ${escapeHtml(
                            formatDateOnly(
                                session?.inspectionDate
                            )
                        )}
                    </p>

                </div>

                ${severityBadge(
                    item.severity
                )}

            </div>


            <div
                class="grid grid-cols-2
                       gap-3 mt-5"
            >

                <div
                    class="bg-gray-50 rounded-lg p-3"
                >
                    <p class="text-xs text-gray-400">
                        Overall
                    </p>

                    <p
                        class="font-bold
                               text-[#0F2D5C] mt-1"
                    >
                        ${escapeHtml(
                            formatComparisonValue(
                                item
                            )
                        )}
                    </p>
                </div>


                <div
                    class="bg-gray-50 rounded-lg p-3"
                >
                    <p class="text-xs text-gray-400">
                        Measurement Point
                    </p>

                    <p
                        class="font-semibold
                               text-[#0F2D5C] mt-1"
                    >
                        ${escapeHtml(
                            item.point || '-'
                        )}
                    </p>

                    <p class="text-xs text-gray-500">
                        ${escapeHtml(
                            item.direction || '-'
                        )}
                    </p>
                </div>


                <div
                    class="bg-gray-50 rounded-lg p-3"
                >
                    <p class="text-xs text-gray-400">
                        Timestamp
                    </p>

                    <p
                        class="font-semibold
                               text-gray-700 mt-1"
                    >
                        ${escapeHtml(
                            formatDateTime(
                                timestamp
                            )
                        )}
                    </p>
                </div>


                <div
                    class="bg-gray-50 rounded-lg p-3"
                >
                    <p class="text-xs text-gray-400">
                        Severity
                    </p>

                    <div class="mt-1">
                        ${severityBadge(
                            item.severity
                        )}
                    </div>
                </div>

            </div>

        </div>
    `;

}


/*
|--------------------------------------------------------------------------
| RENDER COMPARISON
|--------------------------------------------------------------------------
*/

function renderComparison() {

    const result =
        document.getElementById(
            'comparisonResult'
        );


    if (!result) {
        return;
    }


    const itemA =
        getSelectedComparisonSession('A');

    const itemB =
        getSelectedComparisonSession('B');


    if (!itemA || !itemB) {

        result.innerHTML = `
            <div
                class="bg-gray-50 rounded-xl
                       p-8 text-center
                       text-gray-400"
            >
                Select Measurement A and Measurement B.
            </div>
        `;

        return;

    }


    if (
        itemA.equipmentId ===
            itemB.equipmentId &&
        itemA.historyIndex ===
            itemB.historyIndex
    ) {

        result.innerHTML = `
            <div
                class="bg-yellow-50
                       border border-yellow-200
                       rounded-xl p-5
                       text-center
                       text-yellow-700 text-sm"
            >
                Please select two different
                inspection sessions.
            </div>
        `;

        return;

    }


    const sessionA =
        itemA.session;

    const sessionB =
        itemB.session;


    const measurementsA =
        getMeasurementMap(
            sessionA
        );

    const measurementsB =
        getMeasurementMap(
            sessionB
        );


    const keys =
        Array.from(
            new Set([
                ...measurementsA.keys(),
                ...measurementsB.keys()
            ])
        );


    /*
    |--------------------------------------------------------------------------
    | SUMMARY
    |--------------------------------------------------------------------------
    */

    const allRows =
        keys.map(
            function (key) {

                const measurementA =
                    measurementsA.get(
                        key
                    );

                const measurementB =
                    measurementsB.get(
                        key
                    );


                let delta = null;
                let deltaPercent = null;


                if (
                    measurementA &&
                    measurementB
                ) {

                    const valueA =
                        velocityToMm(
                            measurementA.overall,
                            measurementA.unit ||
                            'mm/s RMS'
                        );


                    const valueB =
                        velocityToMm(
                            measurementB.overall,
                            measurementB.unit ||
                            'mm/s RMS'
                        );


                    if (
                        valueA !== null &&
                        valueB !== null
                    ) {

                        delta =
                            vibrationDisplayUnit ===
                            'inch/s RMS'
                                ? (
                                    (valueB - valueA) /
                                    25.4
                                )
                                : (
                                    valueB - valueA
                                );


                        deltaPercent =
                            getDeltaPercent(
                                valueA,
                                valueB
                            );

                    }

                }


                return {
                    key,
                    measurementA,
                    measurementB,
                    delta,
                    deltaPercent
                };

            }
        );


    /*
    |--------------------------------------------------------------------------
    | HIGHLIGHT
    |--------------------------------------------------------------------------
    */

    const commonRows =
        allRows.filter(
            function (row) {

                return (
                    row.measurementA &&
                    row.measurementB
                );

            }
        );


    let totalIncrease = 0;
    let totalDecrease = 0;


    commonRows.forEach(
        function (row) {

            const delta =
                Number(
                    row.deltaPercent
                );


            if (Number.isFinite(delta)) {

                if (delta > 0) {
                    totalIncrease++;
                }

                if (delta < 0) {
                    totalDecrease++;
                }

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | DETAIL CARDS
    |--------------------------------------------------------------------------
    */

    const firstCommon =
        commonRows[0];


    const detailA =
        firstCommon
            ? firstCommon.measurementA
            : null;

    const detailB =
        firstCommon
            ? firstCommon.measurementB
            : null;


    /*
    |--------------------------------------------------------------------------
    | RESULT HTML
    |--------------------------------------------------------------------------
    */

    result.innerHTML = `

        {{-- =============================================== --}}
        {{-- SESSION HEADER --}}
        {{-- =============================================== --}}

        <div
            class="grid grid-cols-1
                   md:grid-cols-2 gap-4"
        >

            <div
                class="rounded-xl
                       border border-gray-200
                       bg-gray-50 p-4"
            >

                <p class="text-xs uppercase
                          tracking-wide
                          text-gray-400
                          font-semibold">
                    Measurement A
                </p>

                <p
                    class="font-bold
                           text-[#0F2D5C] mt-1"
                >
                    ${escapeHtml(
                        itemA.equipmentName
                    )}
                </p>

                <p class="text-sm text-gray-500">
                    ${escapeHtml(
                        formatDateOnly(
                            sessionA.inspectionDate
                        )
                    )}
                </p>

            </div>


            <div
                class="rounded-xl
                       border border-gray-200
                       bg-gray-50 p-4"
            >

                <p class="text-xs uppercase
                          tracking-wide
                          text-gray-400
                          font-semibold">
                    Measurement B
                </p>

                <p
                    class="font-bold
                           text-[#0F2D5C] mt-1"
                >
                    ${escapeHtml(
                        itemB.equipmentName
                    )}
                </p>

                <p class="text-sm text-gray-500">
                    ${escapeHtml(
                        formatDateOnly(
                            sessionB.inspectionDate
                        )
                    )}
                </p>

            </div>

        </div>


        {{-- =============================================== --}}
        {{-- HIGHLIGHT --}}
        {{-- =============================================== --}}

        <div class="mt-5">

            <div class="flex items-center
                        justify-between mb-3">

                <div>

                    <h3
                        class="font-bold
                               text-[#0F2D5C]"
                    >
                        Comparison Highlights
                    </h3>

                    <p class="text-xs
                              text-gray-500 mt-1">
                        Based on measurement points available in both sessions.
                    </p>

                </div>

                <span
                    class="text-xs text-gray-400"
                >
                    ${commonRows.length} common points
                </span>

            </div>


            <div
                class="grid grid-cols-2
                       md:grid-cols-4 gap-3"
            >

                <div
                    class="bg-gray-50 rounded-xl p-4"
                >
                    <p class="text-xs text-gray-400">
                        Increased
                    </p>

                    <p
                        class="text-xl font-bold
                               text-red-600 mt-1"
                    >
                        ${totalIncrease}
                    </p>

                    <p class="text-xs
                              text-gray-400">
                        points
                    </p>
                </div>


                <div
                    class="bg-gray-50 rounded-xl p-4"
                >
                    <p class="text-xs text-gray-400">
                        Improved
                    </p>

                    <p
                        class="text-xl font-bold
                               text-green-600 mt-1"
                    >
                        ${totalDecrease}
                    </p>

                    <p class="text-xs
                              text-gray-400">
                        points
                    </p>
                </div>


                <div
                    class="bg-gray-50 rounded-xl p-4"
                >
                    <p class="text-xs text-gray-400">
                        Measurement A
                    </p>

                    <p
                        class="text-sm font-bold
                               text-[#0F2D5C] mt-1"
                    >
                        ${escapeHtml(
                            formatDateOnly(
                                sessionA.inspectionDate
                            )
                        )}
                    </p>

                    <p class="text-xs
                              text-gray-400">
                        ${escapeHtml(
                            itemA.equipmentName
                        )}
                    </p>
                </div>


                <div
                    class="bg-gray-50 rounded-xl p-4"
                >
                    <p class="text-xs text-gray-400">
                        Measurement B
                    </p>

                    <p
                        class="text-sm font-bold
                               text-[#0F2D5C] mt-1"
                    >
                        ${escapeHtml(
                            formatDateOnly(
                                sessionB.inspectionDate
                            )
                        )}
                    </p>

                    <p class="text-xs
                              text-gray-400">
                        ${escapeHtml(
                            itemB.equipmentName
                        )}
                    </p>
                </div>

            </div>

        </div>


        {{-- =============================================== --}}
        {{-- DETAIL CARDS --}}
        {{-- =============================================== --}}

        <div class="mt-5">

            <h3
                class="font-bold
                       text-[#0F2D5C] mb-3"
            >
                Measurement Detail
            </h3>

            <div
                class="grid grid-cols-1
                       md:grid-cols-2 gap-4"
            >

                ${renderComparisonDetailCard(
                    'Measurement A',
                    detailA,
                    {
                        ...sessionA,
                        equipmentName:
                            itemA.equipmentName
                    }
                )}

                ${renderComparisonDetailCard(
                    'Measurement B',
                    detailB,
                    {
                        ...sessionB,
                        equipmentName:
                            itemB.equipmentName
                    }
                )}

            </div>

        </div>


        {{-- =============================================== --}}
        {{-- COMPARISON TABLE --}}
        {{-- =============================================== --}}

        <div class="mt-5">

            <div class="mb-3">

                <h3
                    class="font-bold
                           text-[#0F2D5C]"
                >
                    Measurement Point Comparison
                </h3>

                <p class="text-xs text-gray-500 mt-1">
                    A = ${escapeHtml(
                        itemA.equipmentName
                    )} · ${escapeHtml(
                        formatDateOnly(
                            sessionA.inspectionDate
                        )
                    )}
                    &nbsp; | &nbsp;
                    B = ${escapeHtml(
                        itemB.equipmentName
                    )} · ${escapeHtml(
                        formatDateOnly(
                            sessionB.inspectionDate
                        )
                    )}
                </p>

            </div>


            <div
                class="overflow-x-auto
                       border border-gray-200
                       rounded-xl"
            >

                <table class="w-full text-sm">

                    <thead class="bg-gray-50">

                        <tr>

                            <th
                                class="px-4 py-3
                                       text-left
                                       font-semibold
                                       text-gray-600"
                            >
                                Point
                            </th>

                            <th
                                class="px-4 py-3
                                       text-left
                                       font-semibold
                                       text-gray-600"
                            >
                                Direction
                            </th>

                            <th
                                class="px-4 py-3
                                       text-right
                                       font-semibold
                                       text-gray-600"
                            >
                                A · ${escapeHtml(
                                    formatDateOnly(
                                        sessionA.inspectionDate
                                    )
                                )}
                            </th>

                            <th
                                class="px-4 py-3
                                       text-right
                                       font-semibold
                                       text-gray-600"
                            >
                                B · ${escapeHtml(
                                    formatDateOnly(
                                        sessionB.inspectionDate
                                    )
                                )}
                            </th>

                            <th
                                class="px-4 py-3
                                       text-right
                                       font-semibold
                                       text-gray-600"
                            >
                                Δ Value
                            </th>

                            <th
                                class="px-4 py-3
                                       text-right
                                       font-semibold
                                       text-gray-600"
                            >
                                Δ %
                            </th>

                            <th
                                class="px-4 py-3
                                       text-center
                                       font-semibold
                                       text-gray-600"
                            >
                                Severity
                            </th>

                        </tr>

                    </thead>


                    <tbody
                        class="divide-y
                               divide-gray-100"
                    >

                        ${
                            allRows.map(
                                function (row) {

                                    const measurement =
                                        row.measurementA ||
                                        row.measurementB;


                                    const deltaText =
                                        row.delta !== null
                                            ? (
                                                row.delta > 0
                                                    ? '+'
                                                    : ''
                                            ) +
                                            Number(
                                                row.delta
                                            ).toFixed(2) +
                                            ' ' +
                                            vibrationDisplayUnit
                                            : '-';


                                    const severity =
                                        row.measurementB
                                            ?.severity ||
                                        row.measurementA
                                            ?.severity ||
                                        'Pending';


                                    return `

                                        <tr
                                            class="hover:bg-gray-50"
                                        >

                                            <td
                                                class="px-4 py-3
                                                       font-semibold
                                                       text-gray-800
                                                       whitespace-nowrap"
                                            >
                                                ${escapeHtml(
                                                    measurement?.point ||
                                                    '-'
                                                )}
                                            </td>


                                            <td
                                                class="px-4 py-3
                                                       text-gray-600
                                                       whitespace-nowrap"
                                            >
                                                ${escapeHtml(
                                                    measurement?.direction ||
                                                    '-'
                                                )}
                                            </td>


                                            <td
                                                class="px-4 py-3
                                                       text-right
                                                       font-semibold
                                                       text-[#0F2D5C]
                                                       whitespace-nowrap"
                                            >
                                                ${
                                                    row.measurementA
                                                        ? escapeHtml(
                                                            formatComparisonValue(
                                                                row.measurementA
                                                            )
                                                        )
                                                        : '-'
                                                }
                                            </td>


                                            <td
                                                class="px-4 py-3
                                                       text-right
                                                       font-semibold
                                                       text-[#0F2D5C]
                                                       whitespace-nowrap"
                                            >
                                                ${
                                                    row.measurementB
                                                        ? escapeHtml(
                                                            formatComparisonValue(
                                                                row.measurementB
                                                            )
                                                        )
                                                        : '-'
                                                }
                                            </td>


                                            <td
                                                class="px-4 py-3
                                                       text-right
                                                       font-semibold
                                                       whitespace-nowrap
                                                       ${deltaClass(
                                                           row.delta
                                                       )}"
                                            >
                                                ${escapeHtml(
                                                    deltaText
                                                )}
                                            </td>


                                            <td
                                                class="px-4 py-3
                                                       text-right
                                                       font-semibold
                                                       whitespace-nowrap
                                                       ${deltaClass(
                                                           row.deltaPercent
                                                       )}"
                                            >
                                                ${escapeHtml(
                                                    formatDeltaPercent(
                                                        row.deltaPercent
                                                    )
                                                )}
                                            </td>


                                            <td
                                                class="px-4 py-3
                                                       text-center"
                                            >
                                                ${severityBadge(
                                                    severity
                                                )}
                                            </td>

                                        </tr>

                                    `;

                                }
                            ).join('')
                        }

                    </tbody>

                </table>

            </div>

        </div>


        {{-- =============================================== --}}
        {{-- COMPARISON TREND --}}
        {{-- =============================================== --}}

        <div class="mt-5">

            <div class="mb-3">

                <h3
                    class="font-bold
                           text-[#0F2D5C]"
                >
                    Vibration Comparison Trend
                </h3>

                <p class="text-xs text-gray-500 mt-1">
                    Overall velocity comparison across measurement points.
                </p>

            </div>


            <div
                class="w-full h-[360px]
                       border border-gray-200
                       rounded-xl p-3"
            >

                <canvas
                    id="comparisonVibrationChart"
                ></canvas>

            </div>

        </div>

    `;


    /*
    |--------------------------------------------------------------------------
    | CREATE COMPARISON CHART
    |--------------------------------------------------------------------------
    */

    createComparisonChart(
        allRows,
        itemA,
        itemB
    );

}


/*
|--------------------------------------------------------------------------
| COMPARISON CHART
|--------------------------------------------------------------------------
*/

let comparisonVibrationChart =
    null;


function createComparisonChart(
    rows,
    itemA,
    itemB
) {

    const canvas =
        document.getElementById(
            'comparisonVibrationChart'
        );


    if (!canvas) {
        return;
    }


    if (
        typeof Chart ===
        'undefined'
    ) {

        console.warn(
            'Chart.js belum tersedia.'
        );

        return;

    }


    if (
        comparisonVibrationChart
    ) {

        comparisonVibrationChart.destroy();

        comparisonVibrationChart =
            null;

    }


    const labels =
        rows.map(
            function (row) {

                const measurement =
                    row.measurementA ||
                    row.measurementB;


                return (
                    measurement?.point ||
                    '-'
                ) +
                (
                    measurement?.direction
                        ? ` - ${measurement.direction}`
                        : ''
                );

            }
        );


    const dataA =
        rows.map(
            function (row) {

                return row.measurementA
                    ? convertVibrationValue(
                        row.measurementA.overall,
                        row.measurementA.unit ||
                        'mm/s RMS'
                    )
                    : null;

            }
        );


    const dataB =
        rows.map(
            function (row) {

                return row.measurementB
                    ? convertVibrationValue(
                        row.measurementB.overall,
                        row.measurementB.unit ||
                        'mm/s RMS'
                    )
                    : null;

            }
        );


    comparisonVibrationChart =
        new Chart(
            canvas.getContext('2d'),
            {

                type: 'line',

                data: {

                    labels: labels,

                    datasets: [

                        {

                            label:
                                `A · ${itemA.equipmentName} · ${formatDateOnly(itemA.session.inspectionDate)}`,

                            data: dataA,

                            borderWidth: 2,

                            pointRadius: 5,

                            pointHoverRadius: 7,

                            tension: 0.2,

                            spanGaps: true,

                            fill: false

                        },


                        {

                            label:
                                `B · ${itemB.equipmentName} · ${formatDateOnly(itemB.session.inspectionDate)}`,

                            data: dataB,

                            borderWidth: 2,

                            pointRadius: 5,

                            pointHoverRadius: 7,

                            tension: 0.2,

                            spanGaps: true,

                            fill: false

                        }

                    ]

                },


                options: {

                    responsive: true,

                    maintainAspectRatio: false,


                    interaction: {

                        mode: 'index',

                        intersect: false

                    },


                    plugins: {

                        legend: {

                            display: true

                        },


                        tooltip: {

                            callbacks: {

                                title:
                                    function (
                                        tooltipItems
                                    ) {

                                        return (
                                            tooltipItems[0]
                                                ?.label ||
                                            '-'
                                        );

                                    },


                                label:
                                    function (
                                        context
                                    ) {

                                        const value =
                                            context.parsed.y;


                                        if (
                                            !Number.isFinite(
                                                value
                                            )
                                        ) {

                                            return (
                                                context.dataset.label +
                                                ': -'
                                            );

                                        }


                                        return (
                                            context.dataset.label +
                                            ': ' +
                                            value.toFixed(2) +
                                            ' ' +
                                            vibrationDisplayUnit
                                        );

                                    }

                            }

                        }

                    },


                    scales: {

                        y: {

                            beginAtZero: true,

                            title: {

                                display: true,

                                text:
                                    vibrationDisplayUnit

                            }

                        },


                        x: {

                            title: {

                                display: true,

                                text:
                                    'Measurement Point'

                            }

                        }

                    }

                }

            }
        );

}

function openEquipmentDetail(equipmentId) {

    const data = equipmentDetailData[String(equipmentId)];

    if (!data) {
        return;
    }

    activeEquipmentData = data;

    const modal = document.getElementById('equipmentDetailModal');
    const title = document.getElementById('equipmentDetailTitle');
    const subtitle = document.getElementById('equipmentDetailSubtitle');

    title.textContent = data.name;
    subtitle.textContent = `${data.area} · ${data.history.length} inspection session`;

    renderEquipmentHistory();

    modal.classList.remove('hidden');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('overflow-hidden');
}

function renderEquipmentHistory() {

    const historyView = document.getElementById('equipmentHistoryView');
    const measurementView = document.getElementById('equipmentMeasurementView');
    const content = document.getElementById('equipmentHistoryContent');

    historyView.classList.remove('hidden');
    measurementView.classList.add('hidden');

    if (!activeEquipmentData || !activeEquipmentData.history.length) {
        content.innerHTML = `
            <div class="bg-gray-50 rounded-xl p-8 text-center text-gray-500">
                Belum ada inspection session untuk equipment ini.
            </div>
        `;
        return;
    }

    content.innerHTML = activeEquipmentData.history.map((session, index) => `
        <button
            type="button"
            onclick="openInspectionDetail(${index})"
            class="w-full text-left border border-gray-200 rounded-xl p-4 hover:border-[#0F2D5C] hover:bg-gray-50 transition group"
        >
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-bold text-[#0F2D5C]">
                            ${escapeHtml(formatDateOnly(session.inspectionDate))}
                        </span>
                        ${severityBadge(session.severity)}
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-6 gap-y-1 mt-3 text-sm text-gray-500">
                        <span>Inspector: <strong class="text-gray-700">${escapeHtml(session.inspector || '-')}</strong></span>
                        <span>
                            Highest:
                            <strong class="text-gray-700">
                                ${formatVelocityInline(session.highestOverall, 'mm/s RMS')}
                            </strong>
                        </span>
                        <span>Points: <strong class="text-gray-700">${session.measurementCount}</strong></span>
                    </div>

                    ${session.remarks ? `<p class="text-xs text-gray-400 mt-2">${escapeHtml(session.remarks)}</p>` : ''}
                </div>

                <span class="shrink-0 px-4 py-2 rounded-lg bg-[#0F2D5C] text-white text-sm font-medium group-hover:bg-blue-900">
                    View Measurements →
                </span>
            </div>
        </button>
    `).join('');
}

function openInspectionDetail(historyIndex) {

    if (!activeEquipmentData) {
        return;
    }

    const session = activeEquipmentData.history[historyIndex];

    if (!session) {
        return;
    }

    activeHistoryIndex = historyIndex;

    const historyView = document.getElementById('equipmentHistoryView');
    const measurementView = document.getElementById('equipmentMeasurementView');
    const title = document.getElementById('measurementDetailTitle');
    const subtitle = document.getElementById('measurementDetailSubtitle');
    const summary = document.getElementById('measurementSummary');
    const content = document.getElementById('measurementDetailContent');

    historyView.classList.add('hidden');
    measurementView.classList.remove('hidden');

    title.textContent = `Measurement Detail · ${activeEquipmentData.name}`;
    subtitle.textContent = `Inspection ${formatDateOnly(session.inspectionDate)} · Inspector: ${session.inspector || '-'}`;

    updateAnalysisReportButton(session);

    const highestPointLabel = session.highestPoint
        ? `${escapeHtml(session.highestPoint)}${session.highestDirection ? ` - ${escapeHtml(session.highestDirection)}` : ''}`
        : '-';

    summary.innerHTML = `
        ${summaryCard('Severity', severityBadge(session.severity))}
        ${summaryCard(
            'Highest Overall',
            `${formatVelocityPair(session.highestOverall, 'mm/s RMS')}<span class="block text-xs text-gray-500 mt-1">Point: ${highestPointLabel}</span>`
        )}
        ${summaryCard('Measurement Points', `${session.measurementCount} point${session.measurementCount === 1 ? '' : 's'}`)}
        ${summaryCard('Inspection Date', formatDateOnly(session.inspectionDate))}
    `;

    if (!session.measurements || session.measurements.length === 0) {
        content.innerHTML = `
            <div class="bg-gray-50 rounded-xl p-8 text-center text-gray-500">
                Tidak ada measurement result pada inspection session ini.
            </div>
        `;
        return;
    }

        const operatingParameters = session.operatingParameters || {};

    const parameterValue = (key, unit) => {
        const value = operatingParameters[key];

        if (
            value === null ||
            value === undefined ||
            value === ''
        ) {
            return `
                <span class="text-gray-400">-</span>
                <span class="text-sm font-normal text-gray-400">
                    ${unit}
                </span>
            `;
        }

        return `
            <span class="text-xl font-semibold text-[#0F2D5C]">
                ${escapeHtml(String(value))}
            </span>
            <span class="text-sm font-normal text-gray-500">
                ${unit}
            </span>
        `;
    };

    content.innerHTML = `
        {{-- OPERATING PARAMETERS --}}
        <div class="bg-white border border-gray-200 rounded-xl p-6 mb-4">

            <div class="flex flex-col md:flex-row
                        md:items-center md:justify-between gap-3 mb-5">

                <div>
                    <h3 class="text-lg font-bold text-[#0F2D5C]">
                        Operating Parameters
                    </h3>

                    <p class="text-sm text-gray-500 mt-1">
                        Operating condition during measurement
                    </p>
                </div>

                ${
                    currentUserIsAdmin
                        ? `
                            <a
                                href="/equipment-inspection/${session.id}/operating-parameters/edit"
                                class="inline-flex items-center justify-center
                                       px-4 py-2 rounded-lg
                                       border border-[#0F2D5C]
                                       text-[#0F2D5C]
                                       hover:bg-blue-50
                                       text-sm font-semibold"
                            >
                                Edit Operating Parameters
                            </a>
                          `
                        : ''
                }

            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2
                        lg:grid-cols-4 gap-5">

                <div>
                    <p class="text-sm text-gray-500">
                        Speed
                    </p>
                    <p class="mt-1">
                        ${parameterValue('speed_rpm', 'RPM')}
                    </p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">
                        Suction Pressure
                    </p>
                    <p class="mt-1">
                        ${parameterValue('suction_pressure', 'Psi')}
                    </p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">
                        Discharge Pressure
                    </p>
                    <p class="mt-1">
                        ${parameterValue('discharge_pressure', 'Psi')}
                    </p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">
                        Flow Rate
                    </p>
                    <p class="mt-1">
                        ${parameterValue('flow_rate', 'USGPM')}
                    </p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">
                        Bearing Temp M-Out
                    </p>
                    <p class="mt-1">
                        ${parameterValue('bearing_temp_m_out', '°C')}
                    </p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">
                        Bearing Temp M-In
                    </p>
                    <p class="mt-1">
                        ${parameterValue('bearing_temp_m_in', '°C')}
                    </p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">
                        Bearing Temp P-In
                    </p>
                    <p class="mt-1">
                        ${parameterValue('bearing_temp_p_in', '°C')}
                    </p>
                </div>

                <div>
                    <p class="text-sm text-gray-500">
                        Bearing Temp P-Out
                    </p>
                    <p class="mt-1">
                        ${parameterValue('bearing_temp_p_out', '°C')}
                    </p>
                </div>

            </div>

        </div>

        {{-- MEASUREMENT RESULTS --}}
        <div class="overflow-x-auto border border-gray-200 rounded-xl">

            <table class="w-full text-sm">

                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">
                            Point
                        </th>

                        <th class="px-4 py-3 text-left font-semibold text-gray-600">
                            Location
                        </th>

                        <th class="px-4 py-3 text-left font-semibold text-gray-600">
                            Direction
                        </th>

                        <th class="px-4 py-3 text-right font-semibold text-gray-600">
                            Overall RMS
                        </th>

                        <th class="px-4 py-3 text-center font-semibold text-gray-600">
                            Severity
                        </th>

                        <th class="px-4 py-3 text-right font-semibold text-gray-600">
                            Crest Factor
                        </th>

                        <th class="px-4 py-3 text-left font-semibold text-gray-600">
                            Timestamp
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">

                    ${session.measurements.map(m => `

                        <tr class="hover:bg-gray-50">

                            <td class="px-4 py-3 font-semibold text-gray-800 whitespace-nowrap">
                                ${escapeHtml(m.point)}
                            </td>

                            <td class="px-4 py-3 text-gray-600">
                                ${escapeHtml(m.location || '-')}
                            </td>

                            <td class="px-4 py-3 text-gray-600">
                                ${escapeHtml(m.direction || '-')}
                            </td>

                            <td class="px-4 py-3 text-right font-semibold text-[#0F2D5C] whitespace-nowrap">
                                ${formatVelocityPair(
                                    m.overall,
                                    m.unit || 'mm/s RMS'
                                )}
                            </td>

                            <td class="px-4 py-3 text-center">
                                ${severityBadge(m.severity)}
                            </td>

                            <td class="px-4 py-3 text-right text-gray-700">
                                ${
                                    m.crest !== null &&
                                    m.crest !== undefined
                                        ? escapeHtml(String(m.crest))
                                        : '-'
                                }
                            </td>

                            <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                ${
                                    m.datetime
                                        ? escapeHtml(
                                            formatDateTime(m.datetime)
                                        )
                                        : '-'
                                }
                            </td>

                        </tr>

                    `).join('')}

                </tbody>

            </table>

        </div>
    `;
}

function updateAnalysisReportButton(session) {
    const button =
        document.getElementById('analysisReportButton');

    const editButton =
        document.getElementById('editAnalysisReportButton');

    if (!button) {
        return;
    }

    const hasReport =
        !!session?.reportFile;


    /*
    |--------------------------------------------------------------------------
    | ADMIN
    |--------------------------------------------------------------------------
    */

    if (currentUserIsAdmin) {

        button.classList.remove('hidden');

        button.disabled = false;

        button.classList.remove(
            'opacity-50',
            'cursor-not-allowed'
        );


        if (hasReport) {

            // View existing report
            button.innerHTML =
                '📄 View Analysis Report';

            button.title =
                'Open analysis report';


            // Show Edit button
            if (editButton) {
                editButton.classList.remove('hidden');
            }

        } else {

            // No report yet → Upload
            button.innerHTML =
                '📤 Upload Analysis Report';

            button.title =
                'Upload Analysis Report untuk equipment inspection ini';


            // Hide Edit button
            if (editButton) {
                editButton.classList.add('hidden');
            }
        }

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | VIEWER
    |--------------------------------------------------------------------------
    */

    // Viewer never gets Edit button
    if (editButton) {
        editButton.classList.add('hidden');
    }


    if (hasReport) {

        // Viewer can open existing report
        button.classList.remove('hidden');

        button.disabled = false;

        button.classList.remove(
            'opacity-50',
            'cursor-not-allowed'
        );

        button.innerHTML =
            '📄 View Analysis Report';

        button.title =
            'Open analysis report';

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | VIEWER - REPORT PENDING
    |--------------------------------------------------------------------------
    */

    button.classList.remove('hidden');

    button.disabled = true;

    button.classList.add(
        'opacity-50',
        'cursor-not-allowed'
    );

    button.innerHTML =
        '⏳ Analysis Report Pending';

    button.title =
        'Analysis Report belum tersedia';
}
function handleAnalysisReport() {
    if (
        !activeEquipmentData ||
        activeHistoryIndex === null ||
        activeHistoryIndex === undefined
    ) {
        return;
    }

    const session =
        activeEquipmentData.history[activeHistoryIndex];

    if (!session) {
        return;
    }

    if (session.reportFile) {
        window.open(
            `/equipment-inspection/${session.id}/analysis-report`,
            '_blank',
            'noopener,noreferrer'
        );

        return;
    }

    // Viewer is read-only and cannot upload a report.
    if (!currentUserIsAdmin) {
        return;
    }

    const input = document.getElementById('analysisReportInput');

    if (input) {
        input.value = '';
        input.click();
    }
}
async function uploadAnalysisReport(input) {
    if (!currentUserIsAdmin) {
        if (input) {
            input.value = '';
        }
        return;
    }

    if (!input || !input.files || !input.files.length) {
        return;
    }

    if (
        !activeEquipmentData ||
        activeHistoryIndex === null ||
        activeHistoryIndex === undefined
    ) {
        input.value = '';
        return;
    }

    const session =
        activeEquipmentData.history[activeHistoryIndex];

    if (!session) {
        input.value = '';
        return;
    }

    const file = input.files[0];

    if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
        alert('Analysis Report harus berupa file PDF.');
        input.value = '';
        return;
    }

    if (file.size > 20 * 1024 * 1024) {
        alert('Ukuran Analysis Report maksimal 20 MB.');
        input.value = '';
        return;
    }

    const button = document.getElementById('analysisReportButton');
    const originalText = button ? button.innerHTML : '';

    if (button) {
        button.disabled = true;
        button.innerHTML = '⏳ Uploading...';
        button.classList.add('opacity-60', 'cursor-wait');
    }

    const formData = new FormData();
    formData.append('analysis_report', file);

    const csrfToken =
        document.getElementById('analysisReportCsrf')?.value || '';

    try {
        const response = await fetch(
            `/equipment-inspection/${session.id}/analysis-report`,
            {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: formData,
            }
        );

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            const validationMessage =
                data?.errors?.analysis_report?.[0] ||
                data?.message ||
                'Upload Analysis Report gagal.';

            throw new Error(validationMessage);
        }

        session.reportFile = data.reportFile || true;

        updateAnalysisReportButton(session);

        alert('Analysis Report berhasil di-upload.');

        window.open(
            data.reportUrl ||
            `/equipment-inspection/${session.id}/analysis-report`,
            '_blank',
            'noopener,noreferrer'
        );
    } catch (error) {
        console.error(error);

        alert(
            error?.message ||
            'Terjadi kesalahan saat upload Analysis Report.'
        );

        if (button) {
            button.disabled = false;
            button.innerHTML = originalText || '📄 Analysis Report';
            button.classList.remove('opacity-60', 'cursor-wait');
        }
    } finally {
        input.value = '';

        if (button) {
            button.disabled = false;
            button.classList.remove('opacity-60', 'cursor-wait');
        }
    }
}
function editAnalysisReport() {
    if (!currentUserIsAdmin) {
        return;
    }

    if (
        !activeEquipmentData ||
        activeHistoryIndex === null ||
        activeHistoryIndex === undefined
    ) {
        return;
    }

    const session =
        activeEquipmentData.history[activeHistoryIndex];

    if (!session) {
        return;
    }

    const input =
        document.getElementById('analysisReportInput');

    if (!input) {
        return;
    }

    input.value = '';
    input.click();
}
function backToEquipmentHistory() {
    activeHistoryIndex = null;
    renderEquipmentHistory();
}

function closeEquipmentDetail() {
    const modal = document.getElementById('equipmentDetailModal');

    modal.classList.add('hidden');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('overflow-hidden');
    activeEquipmentData = null;
    activeHistoryIndex = null;

    const reportInput = document.getElementById('analysisReportInput');

    if (reportInput) {
        reportInput.value = '';
    }
}

function summaryCard(label, valueHtml) {
    return `
        <div class="bg-gray-50 rounded-xl p-4">
            <p class="text-xs text-gray-500">${escapeHtml(label)}</p>
            <div class="font-semibold text-[#0F2D5C] mt-1">${valueHtml}</div>
        </div>
    `;
}

function severityBadge(severity) {
    const styles = {
        Normal: 'bg-green-100 text-green-700',
        Alert: 'bg-yellow-100 text-yellow-700',
        Danger: 'bg-orange-100 text-orange-700',
        Alarm: 'bg-orange-100 text-orange-700',
        Critical: 'bg-red-100 text-red-700',
        Fault: 'bg-red-100 text-red-700',
    };

    const style = styles[severity] || 'bg-gray-100 text-gray-600';

    return `<span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold ${style}">${escapeHtml(severity || 'Pending')}</span>`;
}

function formatNumber(value, decimals = 2) {
    if (value === null || value === undefined || value === '') {
        return '-';
    }

    const number = Number(value);

    return Number.isFinite(number)
        ? number.toFixed(decimals)
        : '-';
}

/*
|--------------------------------------------------------------------------
| VIBRATION DISPLAY UNIT
|--------------------------------------------------------------------------
|
| Raw value from database / ODX remains unchanged.
| The database value is treated as the source value and is converted
| only for UI display.
|
| Display unit is shared across the whole dashboard:
| - Vibration Trend
| - Current / Previous
| - Highest Vibration
| - Equipment Condition by Area
| - Equipment Measurement Detail
|
| 1 inch/s = 25.4 mm/s
|--------------------------------------------------------------------------
*/

const VIBRATION_UNIT_STORAGE_KEY =
    'cbm_vibration_display_unit';


let vibrationDisplayUnit =
    localStorage.getItem(
        VIBRATION_UNIT_STORAGE_KEY
    );


if (
    vibrationDisplayUnit !== 'mm/s RMS' &&
    vibrationDisplayUnit !== 'inch/s RMS'
) {
    vibrationDisplayUnit = 'mm/s RMS';
}


/*
|--------------------------------------------------------------------------
| VELOCITY UNIT HELPERS
|--------------------------------------------------------------------------
*/

function normalizeVelocityUnit(unit) {

    const raw = String(unit || 'mm/s RMS')
        .trim()
        .toLowerCase()
        .replace(/\\/g, '')
        .replace(/\s+/g, ' ');


    if (
        raw.includes('inch/s') ||
        raw.includes('in/s') ||
        raw.includes('inch per second') ||
        raw === 'ips' ||
        raw.includes('ips rms')
    ) {
        return 'inch';
    }


    return 'mm';
}


function velocityToMm(value, unit) {

    const number = Number(value);

    if (!Number.isFinite(number)) {
        return null;
    }


    return normalizeVelocityUnit(unit) === 'inch'
        ? number * 25.4
        : number;
}


function velocityToInch(value, unit) {

    const number = Number(value);

    if (!Number.isFinite(number)) {
        return null;
    }


    return normalizeVelocityUnit(unit) === 'inch'
        ? number
        : number / 25.4;
}


function convertVibrationValue(value, sourceUnit = 'mm/s RMS') {

    const mm = velocityToMm(
        value,
        sourceUnit
    );


    if (mm === null) {
        return null;
    }


    return vibrationDisplayUnit === 'inch/s RMS'
        ? mm / 25.4
        : mm;
}


function formatVibrationValue(
    value,
    sourceUnit = 'mm/s RMS'
) {

    const convertedValue =
        convertVibrationValue(
            value,
            sourceUnit
        );


    if (convertedValue === null) {
        return '-';
    }


    return (
        convertedValue.toFixed(2) +
        ' ' +
        vibrationDisplayUnit
    );
}


function formatVelocityPair(
    value,
    unit = 'mm/s RMS'
) {

    return formatVibrationValue(
        value,
        unit
    );
}


function formatVelocityInline(
    value,
    unit = 'mm/s RMS'
) {

    return formatVibrationValue(
        value,
        unit
    );
}


/*
|--------------------------------------------------------------------------
| UPDATE ALL DASHBOARD VIBRATION VALUES
|--------------------------------------------------------------------------
*/

function updateAllVibrationDisplays() {

    document
        .querySelectorAll('[data-vibration-value]')
        .forEach(function (element) {

            const value =
                element.dataset.vibrationValue;

            const sourceUnit =
                element.dataset.vibrationUnit ||
                'mm/s RMS';


            element.textContent =
                formatVibrationValue(
                    value,
                    sourceUnit
                );

        });

}


/*
|--------------------------------------------------------------------------
| CHANGE DISPLAY UNIT
|--------------------------------------------------------------------------
*/

function setVibrationDisplayUnit(unit) {

    if (
        unit !== 'mm/s RMS' &&
        unit !== 'inch/s RMS'
    ) {
        return;
    }


    vibrationDisplayUnit =
        unit;


    localStorage.setItem(
        VIBRATION_UNIT_STORAGE_KEY,
        vibrationDisplayUnit
    );


    /*
    | Update every display that uses the shared
    | vibration unit. Chart-specific functions are
    | called only when they exist.
    */

    updateAllVibrationDisplays();


    if (typeof updateVibrationUnitUI === 'function') {
        updateVibrationUnitUI();
    }


    if (typeof updateTrendSummary === 'function') {
        updateTrendSummary();
    }


    if (typeof updateVibrationChart === 'function') {
        updateVibrationChart();
    }

}


/*
|--------------------------------------------------------------------------
| INITIALIZE STATIC DASHBOARD VIBRATION VALUES
|--------------------------------------------------------------------------
*/

updateAllVibrationDisplays();


function formatDateOnly(value) {

    if (!value) {
        return '-';
    }


    const raw =
        String(value)
            .replace('T', ' ')
            .substring(0, 10);


    const parts =
        raw.split('-');


    if (parts.length !== 3) {
        return raw;
    }


    const year = parts[0];
    const month = parts[1];
    const day = parts[2];


    const monthNames = [
        'Jan',
        'Feb',
        'Mar',
        'Apr',
        'May',
        'Jun',
        'Jul',
        'Aug',
        'Sep',
        'Oct',
        'Nov',
        'Dec'
    ];


    const monthIndex =
        Number(month) - 1;


    const monthName =
        monthNames[monthIndex] ||
        month;


    return `${day} ${monthName} ${year}`;

}

function formatDateTime(value) {
    if (!value) {
        return '-';
    }

    /*
     * measurement_datetime dari database sudah merupakan
     * waktu lokal WIB.
     *
     * Jangan parsing menggunakan JavaScript Date karena
     * bisa menyebabkan konversi timezone +7 jam.
     */

    const raw = String(value)
        .replace('T', ' ')
        .substring(0, 19);

    const parts = raw.split(' ');

    if (parts.length < 2) {
        return raw;
    }

    const dateParts = parts[0].split('-');
    const time = parts[1];

    if (dateParts.length !== 3) {
        return raw;
    }

    const year = dateParts[0];
    const month = dateParts[1];
    const day = dateParts[2];

    return `${day}-${month}-${year} ${time}`;
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        closeEquipmentDetail();
    }
});

</script>

{{-- ========================================================= --}}
{{-- CHART.JS --}}
{{-- ========================================================= --}}


    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>

        /*
        |--------------------------------------------------------------------------
        | RAW TREND DATA
        |--------------------------------------------------------------------------
        */

        const trendLabels =
            @json($trendLabels);

        const trendValues =
            @json($trendValues);

        const trendTimestamps =
            @json($trendTimestamps ?? []);


        /*
        |--------------------------------------------------------------------------
        | CHART INSTANCE
        |--------------------------------------------------------------------------
        */

        let dashboardVibrationChart = null;


        /*
        |--------------------------------------------------------------------------
        | UPDATE UNIT BUTTON UI
        |--------------------------------------------------------------------------
        */

        function updateVibrationUnitUI() {

            const mmButton =
                document.getElementById(
                    'unitMmSButton'
                );

            const inchButton =
                document.getElementById(
                    'unitInchSButton'
                );


            const activeClasses =
                [
                    'bg-[#0F2D5C]',
                    'text-white',
                    'shadow-sm'
                ];

            const inactiveClasses =
                [
                    'text-gray-600',
                    'hover:bg-gray-100'
                ];


            if (mmButton) {

                mmButton.classList.remove(
                    ...activeClasses,
                    ...inactiveClasses
                );

                if (
                    vibrationDisplayUnit ===
                    'mm/s RMS'
                ) {

                    mmButton.classList.add(
                        ...activeClasses
                    );

                } else {

                    mmButton.classList.add(
                        ...inactiveClasses
                    );

                }

            }


            if (inchButton) {

                inchButton.classList.remove(
                    ...activeClasses,
                    ...inactiveClasses
                );

                if (
                    vibrationDisplayUnit ===
                    'inch/s RMS'
                ) {

                    inchButton.classList.add(
                        ...activeClasses
                    );

                } else {

                    inchButton.classList.add(
                        ...inactiveClasses
                    );

                }

            }

        }


        /*
        |--------------------------------------------------------------------------
        | UPDATE CURRENT / PREVIOUS
        |--------------------------------------------------------------------------
        */

        function updateTrendSummary() {

            const currentElement =
                document.getElementById(
                    'currentTrendValueDisplay'
                );

            const previousElement =
                document.getElementById(
                    'previousTrendValueDisplay'
                );


            if (currentElement) {

                const currentValue =
                    currentElement.dataset.value;

                currentElement.textContent =
                    formatVibrationValue(
                        currentValue
                    );

            }


            if (previousElement) {

                const previousValue =
                    previousElement.dataset.value;

                previousElement.textContent =
                    formatVibrationValue(
                        previousValue
                    );

            }

        }


        /*
        |--------------------------------------------------------------------------
        | UPDATE CHART DATA / LABEL / AXIS
        |--------------------------------------------------------------------------
        */

        function updateVibrationChart() {

            if (!dashboardVibrationChart) {
                return;
            }


            dashboardVibrationChart.data.datasets[0].data =
                trendValues.map(function (value) {

                    return convertVibrationValue(
                        value
                    );

                });


            dashboardVibrationChart.data.datasets[0].label =
                'Overall Velocity (' +
                vibrationDisplayUnit +
                ')';


            dashboardVibrationChart.options.scales.y.title.text =
                vibrationDisplayUnit;


            dashboardVibrationChart.update();

        }


        /*
        |--------------------------------------------------------------------------
        | CREATE CHART
        |--------------------------------------------------------------------------
        */

        const chartElement =
            document.getElementById(
                'dashboardVibrationTrend'
            );


        if (chartElement) {

            const ctx =
                chartElement.getContext('2d');


            dashboardVibrationChart =
                new Chart(ctx, {

                    type: 'line',

                    data: {

                        labels:
                            trendLabels,

                        datasets: [

                            {

                                label:
                                    'Overall Velocity (' +
                                    vibrationDisplayUnit +
                                    ')',

                                data:
                                    trendValues.map(
                                        function (value) {
                                            return convertVibrationValue(
                                                value
                                            );
                                        }
                                    ),

                                borderWidth: 2,

                                pointRadius: 5,

                                pointHoverRadius: 7,

                                tension: 0.25,

                                fill: false

                            }

                        ]

                    },


                    options: {

                        responsive: true,

                        maintainAspectRatio: false,


                        plugins: {

                            legend: {
                                display: true
                            },


                            tooltip: {

                                callbacks: {
    title: function (tooltipItems) {

    const index =
        tooltipItems[0]?.dataIndex;

    const timestamp =
        String(
            trendTimestamps[index] || ''
        )
        .replace('T', ' ')
        .substring(0, 19);

    if (!timestamp) {
        return ['-'];
    }

    const datePart =
        timestamp.substring(0, 10);

    const timePart =
        timestamp.substring(11, 19);

    const parts =
        datePart.split('-');

    if (parts.length !== 3) {
        return [
            datePart,
            'Time: ' + timePart
        ];
    }

    return [
        `${parts[2]}-${parts[1]}-${parts[0]}`,
        'Time: ' + timePart
    ];

},

    beforeBody: function(tooltipItems) {

        const index =
            tooltipItems[0]?.dataIndex;

        if (
            index === undefined ||
            !trendLabels[index]
        ) {
            return [];
        }

        const date =
            new Date(
                trendLabels[index]
            );

        if (isNaN(date.getTime())) {
            return [];
        }

        return [
            'Time: ' +
            date.toLocaleTimeString(
                'en-GB',
                {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                }
            )
        ];

    },

    label: function(context) {

        const value =
            context.parsed.y;

        return (
            context.dataset.label +
            ': ' +
            (
                Number.isFinite(value)
                    ? value.toFixed(2)
                    : '-'
            ) +
            ' ' +
            vibrationDisplayUnit
        );

    }
},


                                    label:
                                        function (
                                            context
                                        ) {

                                            const value =
                                                context.parsed.y;


                                            if (
                                                !Number.isFinite(
                                                    value
                                                )
                                            ) {
                                                return [
                                                    'Overall Velocity: -'
                                                ];
                                            }


                                            return [
                                                'Overall Velocity (' +
                                                vibrationDisplayUnit +
                                                '): ' +
                                                value.toFixed(2) +
                                                ' ' +
                                                vibrationDisplayUnit
                                            ];

                                        }

                                }

                            }

                        }

                    },


                    scales: {

                        y: {

                            beginAtZero: true,

                            title: {

                                display: true,

                                text:
                                    vibrationDisplayUnit

                            }

                        },


                        x: {

                            title: {

                                display: true,

                                text:
                                    'Measurement Date & Time'

                            }

                        }

                    }

                });

        }


        /*
        |--------------------------------------------------------------------------
        | INITIALIZE UNIT UI
        |--------------------------------------------------------------------------
        */

        updateVibrationUnitUI();

        updateTrendSummary();

    </script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

    // chart utama
    // tooltip
    // unit selector
    // comparison chart

</script>

@endsection