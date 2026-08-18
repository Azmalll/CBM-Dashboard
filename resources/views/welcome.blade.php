@extends('layouts.app')

@section('title', 'CBM System')

@section('content')

@php
    /*
    |--------------------------------------------------------------------------
    | HOME DATA
    |--------------------------------------------------------------------------
    | Read existing CBM data directly from the existing models.
    | No database structure or stored measurement values are changed.
    |
    | Severity basis follows the dashboard:
    | < 2.80  = Normal
    | < 4.50  = Alert
    | < 7.10  = Danger
    | >= 7.10 = Critical
    |--------------------------------------------------------------------------
    */

    $determineSeverity = static function ($overall): string {
        if ($overall === null) {
            return 'Pending';
        }

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


    /*
    |--------------------------------------------------------------------------
    | EQUIPMENT + LATEST INSPECTION
    |--------------------------------------------------------------------------
    */

    $homeEquipments =
        \App\Models\Equipment::orderBy('area')
            ->orderBy('equipment_name')
            ->get();


    $equipmentInspections =
        \App\Models\EquipmentInspection::with([
            'inspection',
            'equipment',
            'measurementResults.measurementPoint',
        ])
        ->get()
        ->groupBy('equipment_id');


    $latestEquipment = collect();


    foreach ($homeEquipments as $equipment) {

        $latestInspection =
            $equipmentInspections
                ->get($equipment->id, collect())
                ->sort(function ($a, $b) {

                    $dateA =
                        optional($a->inspection)->inspection_date
                        ?? $a->created_at;

                    $dateB =
                        optional($b->inspection)->inspection_date
                        ?? $b->created_at;

                    $timestampA = strtotime((string) $dateA);
                    $timestampB = strtotime((string) $dateB);

                    if ($timestampA === $timestampB) {
                        return $b->id <=> $a->id;
                    }

                    return $timestampB <=> $timestampA;
                })
                ->first();


        $latestMeasurement = null;

        if ($latestInspection) {

            $latestMeasurement =
                $latestInspection
                    ->measurementResults
                    ->filter(
                        fn ($measurement) =>
                            $measurement->overall_velocity !== null
                    )
                    ->sortByDesc(
                        fn ($measurement) =>
                            (float) $measurement->overall_velocity
                    )
                    ->first();
        }


        $overall =
            $latestMeasurement?->overall_velocity;


        $severity =
            $determineSeverity($overall);


        $latestEquipment->push([
            'equipment' => $equipment,
            'inspection' => $latestInspection,
            'measurement' => $latestMeasurement,
            'overall' => $overall,
            'severity' => $severity,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | CBM HEALTH PULSE
    |--------------------------------------------------------------------------
    |
    | Equipment-level health score:
    | Normal   = 100
    | Alert    = 75
    | Danger   = 50
    | Critical = 25
    |
    | Score = average latest health score of equipment that has measurement.
    |--------------------------------------------------------------------------
    */

    $healthScores = [
        'Normal' => 100,
        'Alert' => 75,
        'Danger' => 50,
        'Critical' => 25,
    ];


    $measuredEquipment =
        $latestEquipment
            ->filter(
                fn ($item) =>
                    $item['overall'] !== null
            );


    $cbmHealthPulse =
        $measuredEquipment->count()
            ? round(
                $measuredEquipment->avg(
                    fn ($item) =>
                        $healthScores[$item['severity']] ?? 0
                )
            )
            : null;


    $healthPulseLabel =
        $cbmHealthPulse === null
            ? 'NO DATA'
            : (
                $cbmHealthPulse >= 85
                    ? 'HEALTHY'
                    : (
                        $cbmHealthPulse >= 70
                            ? 'ATTENTION'
                            : (
                                $cbmHealthPulse >= 50
                                    ? 'DEGRADED'
                                    : 'CRITICAL'
                            )
                    )
            );


    $healthPulseColor =
        $cbmHealthPulse === null
            ? 'text-slate-500'
            : (
                $cbmHealthPulse >= 85
                    ? 'text-emerald-600'
                    : (
                        $cbmHealthPulse >= 70
                            ? 'text-amber-600'
                            : (
                                $cbmHealthPulse >= 50
                                    ? 'text-orange-600'
                                    : 'text-red-600'
                            )
                    )
            );


    $healthPulseRing =
        $cbmHealthPulse === null
            ? 0
            : max(0, min(100, $cbmHealthPulse));


    $healthPulseRingColor =
        $cbmHealthPulse === null
            ? '#cbd5e1'
            : (
                $cbmHealthPulse >= 85
                    ? '#10b981'
                    : (
                        $cbmHealthPulse >= 70
                            ? '#f59e0b'
                            : (
                                $cbmHealthPulse >= 50
                                    ? '#f97316'
                                    : '#ef4444'
                            )
                    )
            );


    /*
    |--------------------------------------------------------------------------
    | AREA HEALTH INDEX
    |--------------------------------------------------------------------------
    */

    $areaHealthIndex =
        $latestEquipment
            ->filter(
                fn ($item) =>
                    $item['overall'] !== null
            )
            ->groupBy(
                fn ($item) =>
                    $item['equipment']->area ?: 'Unassigned'
            )
            ->map(
                function ($items, $area) use ($healthScores) {

                    $score =
                        round(
                            $items->avg(
                                fn ($item) =>
                                    $healthScores[$item['severity']] ?? 0
                            )
                        );


                    $status =
                        $score >= 85
                            ? 'Healthy'
                            : (
                                $score >= 70
                                    ? 'Attention'
                                    : (
                                        $score >= 50
                                            ? 'Degraded'
                                            : 'Critical'
                                    )
                            );


                    return [
                        'area' => $area,
                        'score' => $score,
                        'status' => $status,
                        'equipment_count' => $items->count(),
                    ];
                }
            )
            ->sortBy('area')
            ->values();


    /*
    |--------------------------------------------------------------------------
    | RECENT INSPECTION
    |--------------------------------------------------------------------------
    */

    $recentInspection =
        \App\Models\EquipmentInspection::with([
            'inspection',
            'equipment',
            'measurementResults.measurementPoint',
        ])
        ->get()
        ->sort(function ($a, $b) {

            $dateA =
                optional($a->inspection)->inspection_date
                ?? $a->created_at;

            $dateB =
                optional($b->inspection)->inspection_date
                ?? $b->created_at;

            $timestampA = strtotime((string) $dateA);
            $timestampB = strtotime((string) $dateB);

            if ($timestampA === $timestampB) {
                return $b->id <=> $a->id;
            }

            return $timestampB <=> $timestampA;
        })
        ->first();


    $recentMeasurement =
        $recentInspection
            ? $recentInspection
                ->measurementResults
                ->filter(
                    fn ($measurement) =>
                        $measurement->overall_velocity !== null
                )
                ->sortByDesc(
                    fn ($measurement) =>
                        (float) $measurement->overall_velocity
                )
                ->first()
            : null;


    $recentSeverity =
        $recentMeasurement
            ? $determineSeverity(
                $recentMeasurement->overall_velocity
            )
            : ($recentInspection?->severity ?? 'Pending');


    $recentDate =
        $recentInspection
            ? (
                optional($recentInspection->inspection)->inspection_date
                ?? $recentInspection->created_at
            )
            : null;


    $recentPoint =
        $recentMeasurement
            ? optional($recentMeasurement->measurementPoint)
            : null;


    $recentPointLabel =
        $recentPoint
            ? trim(
                (string) ($recentPoint->point_name ?? '')
                . (
                    !empty($recentPoint->direction)
                        ? ' - ' . $recentPoint->direction
                        : ''
                )
            )
            : 'Measurement Point';


    $recentMm =
        $recentMeasurement
            ? (float) $recentMeasurement->overall_velocity
            : null;


    $recentInch =
        $recentMm !== null
            ? $recentMm / 25.4
            : null;

@endphp


<div class="max-w-7xl mx-auto space-y-6">


    {{-- =====================================================
        HOME HEADER
    ====================================================== --}}

    <section
        class="relative overflow-hidden rounded-3xl
               bg-gradient-to-br from-[#0F2D5C] to-[#173F78]
               text-white shadow-lg px-7 sm:px-10 py-8"
    >

        <div class="relative z-10">

            <p class="text-xs uppercase tracking-[0.22em]
                      text-blue-200 font-semibold">
                Condition Based Maintenance
            </p>

            <div class="flex flex-col sm:flex-row
                        sm:items-end sm:justify-between gap-4">

                <div>

                    <h1 class="text-3xl sm:text-4xl font-bold mt-2">
                        CBM System
                    </h1>

                    <p class="text-blue-100 mt-2 max-w-2xl">
                        Condition Based Maintenance Monitoring System
                    </p>

                </div>


                <div
                    class="inline-flex items-center gap-2
                           self-start sm:self-auto
                           rounded-full bg-white/10
                           border border-white/15
                           px-4 py-2 text-sm"
                >

                    <span class="relative flex h-2.5 w-2.5">
                        <span
                            class="absolute inline-flex h-full w-full
                                   animate-ping rounded-full
                                   bg-emerald-300 opacity-60"
                        ></span>

                        <span
                            class="relative inline-flex h-2.5 w-2.5
                                   rounded-full bg-emerald-400"
                        ></span>
                    </span>

                    System Online

                </div>

            </div>

        </div>

        <div
            class="absolute -right-16 -top-20
                   w-64 h-64 rounded-full bg-white/5"
        ></div>

        <div
            class="absolute right-20 -bottom-28
                   w-72 h-72 rounded-full bg-white/5"
        ></div>

    </section>



    {{-- =====================================================
        CBM HEALTH PULSE
    ====================================================== --}}

    <section
        class="bg-white rounded-3xl shadow-sm
               border border-slate-200 p-7 sm:p-9"
    >

        <div class="text-center">

            <p class="text-xs uppercase tracking-[0.2em]
                      text-slate-400 font-semibold">
                CBM Health Pulse
            </p>

            <div class="mt-5 flex justify-center">

                <div
                    class="relative w-40 h-40 rounded-full
                           flex items-center justify-center"
                    style="background:
                        conic-gradient(
                            {{ $healthPulseRingColor }}
                            {{ $healthPulseRing }}%,
                            #e2e8f0 0
                        );"
                >

                    <div
                        class="absolute inset-[10px]
                               rounded-full bg-white
                               flex flex-col items-center
                               justify-center"
                    >

                        <div
                            class="text-4xl font-bold
                                   {{ $healthPulseColor }}"
                        >
                            {{ $cbmHealthPulse !== null
                                ? number_format($cbmHealthPulse, 0) . '%'
                                : '—' }}
                        </div>

                        <div
                            class="text-xs font-bold tracking-wider
                                   {{ $healthPulseColor }} mt-1"
                        >
                            {{ $healthPulseLabel }}
                        </div>

                    </div>

                </div>

            </div>

            <p class="text-xs text-slate-400 mt-2">
                Latest equipment condition index
            </p>

        </div>

    </section>



    {{-- =====================================================
        AREA HEALTH INDEX
    ====================================================== --}}

    <section
        class="bg-white rounded-3xl shadow-sm
               border border-slate-200 p-6 sm:p-8"
    >

        <div class="flex items-end justify-between gap-4 mb-6">

            <div>

                <p class="text-xs uppercase tracking-[0.2em]
                          text-slate-400 font-semibold">
                    CBM Area Monitoring
                </p>

                <h2 class="text-xl sm:text-2xl font-bold
                           text-[#0F2D5C] mt-1">
                    Area Health Index
                </h2>

                <p class="text-sm text-slate-500 mt-1">
                    Latest equipment condition by monitoring area
                </p>

            </div>

        </div>


        @if($areaHealthIndex->count())

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">

                @foreach($areaHealthIndex as $area)

                    @php

                        $statusClass =
                            $area['status'] === 'Healthy'
                                ? 'bg-emerald-50 text-emerald-700'
                                : (
                                    $area['status'] === 'Attention'
                                        ? 'bg-amber-50 text-amber-700'
                                        : (
                                            $area['status'] === 'Degraded'
                                                ? 'bg-orange-50 text-orange-700'
                                                : 'bg-red-50 text-red-700'
                                        )
                                );


                        $dotClass =
                            $area['status'] === 'Healthy'
                                ? 'bg-emerald-500'
                                : (
                                    $area['status'] === 'Attention'
                                        ? 'bg-amber-500'
                                        : (
                                            $area['status'] === 'Degraded'
                                                ? 'bg-orange-500'
                                                : 'bg-red-500'
                                        )
                                );

                    @endphp


                    <div
                        class="group rounded-2xl border border-slate-200
                               bg-slate-50/70 p-5
                               hover:-translate-y-1 hover:bg-white
                               hover:shadow-md transition duration-200"
                    >

                        <div class="flex items-center justify-between gap-2">

                            <div class="font-bold text-[#0F2D5C]">
                                {{ $area['area'] }}
                            </div>

                            <span
                                class="w-2.5 h-2.5 rounded-full
                                       {{ $dotClass }}"
                            ></span>

                        </div>


                        <div class="mt-4 flex items-end justify-between gap-3">

                            <div>

                                <div class="text-3xl font-bold text-slate-800">
                                    {{ number_format($area['score'], 0) }}
                                </div>

                                <div class="text-[11px] text-slate-400 mt-1">
                                    {{ $area['equipment_count'] }} equipment
                                </div>

                            </div>


                            <span
                                class="text-xs font-semibold rounded-full
                                       px-2.5 py-1 {{ $statusClass }}"
                            >
                                {{ $area['status'] }}
                            </span>

                        </div>

                    </div>

                @endforeach

            </div>

        @else

            <div
                class="rounded-2xl border border-dashed
                       border-slate-300 bg-slate-50
                       px-6 py-10 text-center"
            >

                <div class="text-3xl">📍</div>

                <div class="font-semibold text-slate-700 mt-3">
                    No area health data
                </div>

                <p class="text-sm text-slate-400 mt-1">
                    No equipment with a valid measurement is available.
                </p>

            </div>

        @endif

    </section>



    {{-- =====================================================
        RECENT INSPECTION
    ====================================================== --}}

    <section
        class="bg-white rounded-3xl shadow-sm
               border border-slate-200 p-6 sm:p-8"
    >

        <div class="flex items-center justify-between gap-4">

            <div>

                <p class="text-xs uppercase tracking-[0.2em]
                          text-slate-400 font-semibold">
                    Activity
                </p>

                <h2 class="text-xl font-bold text-[#0F2D5C] mt-1">
                    Recent Inspection
                </h2>

                <p class="text-sm text-slate-500 mt-1">
                    Latest inspection session
                </p>

            </div>


        </div>


        @if($recentInspection && $recentMeasurement)

            <div
                class="mt-5 rounded-2xl bg-slate-50
                       border border-slate-200 p-5 sm:p-6"
            >

                <div class="flex flex-col lg:flex-row
                            lg:items-center lg:justify-between gap-5">

                    <div>

                        <div class="flex flex-wrap items-center gap-2">

                            <div class="font-bold text-lg text-slate-800">
                                {{ $recentInspection->equipment?->equipment_name ?? 'Equipment' }}
                            </div>

                            <span
                                class="inline-flex items-center
                                       rounded-full bg-[#0F2D5C]
                                       px-3 py-1 text-xs font-bold
                                       text-white"
                            >
                                {{ $recentPointLabel }}
                            </span>

                        </div>


                        <div class="text-sm text-slate-500 mt-2">
                            {{ $recentDate
                                ? date('d M Y', strtotime((string) $recentDate))
                                : '-' }}
                        </div>

                    </div>


                    <div class="flex flex-col sm:flex-row
                                sm:items-end gap-4 lg:gap-7">

                        <div class="text-left sm:text-right">

                            <div class="text-lg font-bold text-[#0F2D5C]">

                                {{ number_format($recentMm, 2) }}

                                <span class="text-sm font-medium text-slate-500">
                                    mm/s RMS
                                </span>

                            </div>

                            <div class="text-sm font-semibold text-slate-400 mt-1">

                                {{ number_format($recentInch, 2) }}

                                <span class="text-xs font-medium text-slate-400">
                                    inch/s RMS
                                </span>

                            </div>

                        </div>


                        <div class="sm:text-right">

                            <div
                                class="inline-flex items-center rounded-full
                                       px-3 py-1.5 text-xs font-bold
                                       uppercase tracking-wide
                                       {{
                                            $recentSeverity === 'Normal'
                                                ? 'bg-emerald-50 text-emerald-700'
                                                : (
                                                    $recentSeverity === 'Alert'
                                                        ? 'bg-amber-50 text-amber-700'
                                                        : 'bg-red-50 text-red-700'
                                                )
                                       }}"
                            >
                                {{ $recentSeverity }}
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        @else

            <div
                class="mt-5 rounded-2xl border border-dashed
                       border-slate-300 bg-slate-50
                       px-6 py-8 text-center"
            >

                <div class="text-2xl">📋</div>

                <p class="text-sm text-slate-400 mt-2">
                    No recent inspection data available.
                </p>

            </div>

        @endif

    </section>



    {{-- FOOTER --}}

    <footer class="text-center py-3">

        <p class="text-xs text-slate-400">
            Condition Based Maintenance Monitoring System
        </p>

    </footer>

</div>

@endsection