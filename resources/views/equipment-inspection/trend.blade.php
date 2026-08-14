@extends('layouts.app')

@section('title', 'Vibration Trend')

@section('content')

<div class="space-y-6">

    {{-- ===================================================== --}}
    {{-- HEADER --}}
    {{-- ===================================================== --}}

    <div class="flex justify-between items-start">

        <div>
            <h1 class="text-3xl font-bold text-[#0F2D5C]">
                Vibration Trend
            </h1>

            <p class="text-gray-500 mt-1">
                {{ $equipmentInspection->equipment->equipment_name }}
                - Overall Vibration Monitoring
            </p>
        </div>


        <a
            href="{{ route('equipment-inspection.show', $equipmentInspection->id) }}"
            class="bg-gray-500 hover:bg-gray-600 text-white px-5 py-3 rounded-xl"
        >
            ← Back
        </a>

    </div>


    {{-- ===================================================== --}}
    {{-- FILTER --}}
    {{-- ===================================================== --}}

    <div class="bg-white rounded-2xl shadow p-6">

        <form
            method="GET"
            action="{{ route('equipment-inspection.trend', $equipmentInspection->id) }}"
            class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end"
        >

            {{-- Measurement Point --}}

            <div class="md:col-span-2">

                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Measurement Point
                </label>

                <select
                    name="measurement_point_id"
                    onchange="this.form.submit()"
                    class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500"
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

            </div>


            {{-- Period --}}

            <div>

                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Period
                </label>

                <select
                    name="period"
                    onchange="this.form.submit()"
                    class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500"
                >

                    <option value="7" {{ $period === '7' ? 'selected' : '' }}>
                        Last 7 Days
                    </option>

                    <option value="30" {{ $period === '30' ? 'selected' : '' }}>
                        Last 30 Days
                    </option>

                    <option value="90" {{ $period === '90' ? 'selected' : '' }}>
                        Last 90 Days
                    </option>

                    <option value="365" {{ $period === '365' ? 'selected' : '' }}>
                        Last 1 Year
                    </option>

                    <option value="all" {{ $period === 'all' ? 'selected' : '' }}>
                        All Data
                    </option>

                </select>

            </div>

        </form>


        {{-- Selected Point --}}

        <div class="mt-5 pt-5 border-t">

            <p class="text-sm text-gray-500">
                Selected Point
            </p>

            <p class="text-lg font-semibold text-[#0F2D5C]">

                @if($selectedPoint)

                    {{ $selectedPoint->point_name }}

                    @if($selectedPoint->direction)
                        - {{ $selectedPoint->direction }}
                    @endif

                @else

                    -

                @endif

            </p>

        </div>

    </div>


    {{-- ===================================================== --}}
    {{-- MEASUREMENT SUMMARY --}}
    {{-- ===================================================== --}}

    <div class="bg-white rounded-2xl shadow p-7">

        <h2 class="text-2xl font-bold text-[#0F2D5C] mb-6">
            Measurement Summary
        </h2>


        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

            {{-- Latest --}}

            <div class="bg-gray-50 rounded-xl p-5">

                <p class="text-sm text-gray-500">
                    Latest Overall
                </p>

                <p class="text-2xl font-bold text-[#0F2D5C] mt-2">

                    {{ number_format($latestOverall, 2) }}

                    <span class="text-sm font-normal text-gray-500">
                        mm/s RMS
                    </span>

                </p>

            </div>


            {{-- Highest --}}

            <div class="bg-gray-50 rounded-xl p-5">

                <p class="text-sm text-gray-500">
                    Highest Overall
                </p>

                <p class="text-2xl font-bold text-[#0F2D5C] mt-2">

                    {{ number_format($highestOverall, 2) }}

                    <span class="text-sm font-normal text-gray-500">
                        mm/s RMS
                    </span>

                </p>

            </div>


            {{-- Total --}}

            <div class="bg-gray-50 rounded-xl p-5">

                <p class="text-sm text-gray-500">
                    Total Measurements
                </p>

                <p class="text-2xl font-bold text-[#0F2D5C] mt-2">
                    {{ $totalMeasurements }}
                </p>

            </div>

        </div>

    </div>


    {{-- ===================================================== --}}
    {{-- GRAPH + HISTORY --}}
    {{-- ===================================================== --}}

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">


        {{-- ================================================= --}}
        {{-- GRAPH --}}
        {{-- ================================================= --}}

        <div class="bg-white rounded-2xl shadow p-7">

            <h2 class="text-2xl font-bold text-[#0F2D5C]">
                Overall Vibration Trend
            </h2>

            <p class="text-gray-500 mt-1 mb-6">
                Overall velocity trend in mm/s RMS
            </p>


            @if($trendResults->count())

                <div class="relative h-[360px]">

                    <canvas id="vibrationTrendChart"></canvas>

                </div>

            @else

                <div class="bg-gray-50 rounded-xl p-8 text-center text-gray-500">
                    No measurement history available for this measurement point.
                </div>

            @endif

        </div>


        {{-- ================================================= --}}
        {{-- MEASUREMENT HISTORY --}}
        {{-- ================================================= --}}

        <div class="bg-white rounded-2xl shadow p-7">

            <h2 class="text-2xl font-bold text-[#0F2D5C]">
                Measurement History
            </h2>

            <p class="text-gray-500 mt-1 mb-6">
                Historical vibration measurement for the selected point
            </p>


            @if($trendResults->count())

                <div class="overflow-x-auto">

                    <table class="w-full">

                        <thead class="bg-gray-100">

                            <tr>

                                <th class="px-4 py-3 text-left text-sm">
                                    No
                                </th>

                                <th class="px-4 py-3 text-left text-sm">
                                    Date
                                </th>

                                <th class="px-4 py-3 text-left text-sm">
                                    Overall
                                </th>

                                <th class="px-4 py-3 text-left text-sm">
                                    Peak
                                </th>

                                <th class="px-4 py-3 text-left text-sm">
                                    Crest
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @foreach($trendResults->reverse()->values() as $index => $result)

                                <tr class="border-t">

                                    <td class="px-4 py-3 text-sm">
                                        {{ $index + 1 }}
                                    </td>


                                    <td class="px-4 py-3 text-sm whitespace-nowrap">

                                        @if($result['date'])

                                            {{ \Carbon\Carbon::parse($result['date'])->format('d M Y') }}

                                        @else

                                            -

                                        @endif

                                    </td>


                                    <td class="px-4 py-3 text-sm font-semibold">

                                        {{ number_format($result['overall'], 2) }}

                                        <span class="text-xs text-gray-500">
                                            mm/s RMS
                                        </span>

                                    </td>


                                    <td class="px-4 py-3 text-sm">

                                        {{ $result['peak'] !== null
                                            ? number_format($result['peak'], 2)
                                            : '-' }}

                                    </td>


                                    <td class="px-4 py-3 text-sm">

                                        {{ $result['crest_factor'] !== null
                                            ? number_format($result['crest_factor'], 2)
                                            : '-' }}

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            @else

                <div class="bg-gray-50 rounded-xl p-6 text-center text-gray-500">
                    No measurement history available.
                </div>

            @endif

        </div>

    </div>


    {{-- ===================================================== --}}
    {{-- DIAGNOSIS TREND --}}
    {{-- ===================================================== --}}

    <div class="bg-white rounded-2xl shadow p-7">

        <div class="flex justify-between items-center mb-6">

            <div>

                <h2 class="text-2xl font-bold text-[#0F2D5C]">
                    Diagnosis Trend
                </h2>

                <p class="text-gray-500 mt-1">
                    Historical analyst assessment for the selected measurement point
                </p>

            </div>

        </div>


        @if($diagnosisTrend->count())

            <div class="overflow-x-auto">

                <table class="w-full">

                    <thead class="bg-gray-100">

                        <tr>

                            <th class="px-5 py-4 text-left text-sm">
                                Date
                            </th>

                            <th class="px-5 py-4 text-left text-sm">
                                Severity
                            </th>

                            <th class="px-5 py-4 text-left text-sm">
                                Diagnosis
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @foreach($diagnosisTrend as $item)

                            <tr class="border-t">

                                <td class="px-5 py-4 text-sm">

                                    @if($item['date'])

                                        {{ \Carbon\Carbon::parse($item['date'])->format('d M Y') }}

                                    @else

                                        -

                                    @endif

                                </td>


                                <td class="px-5 py-4">

                                    @if($item['severity'] === 'Normal')

                                        <span class="inline-flex px-3 py-1 rounded-full bg-green-100 text-green-700 text-sm font-semibold">
                                            Normal
                                        </span>

                                    @elseif($item['severity'] === 'Alert')

                                        <span class="inline-flex px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 text-sm font-semibold">
                                            Alert
                                        </span>

                                    @elseif($item['severity'] === 'Alarm')

                                        <span class="inline-flex px-3 py-1 rounded-full bg-orange-100 text-orange-700 text-sm font-semibold">
                                            Alarm
                                        </span>

                                    @elseif($item['severity'] === 'Critical')

                                        <span class="inline-flex px-3 py-1 rounded-full bg-red-100 text-red-700 text-sm font-semibold">
                                            Critical
                                        </span>

                                    @else

                                        <span class="text-gray-400">
                                            Pending
                                        </span>

                                    @endif

                                </td>


                                <td class="px-5 py-4 text-sm font-semibold">

                                    {{ $item['diagnosis'] ?? '-' }}

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        @else

            <div class="bg-gray-50 rounded-xl p-6 text-center text-gray-500">
                No diagnosis history available.
            </div>

        @endif

    </div>

</div>


{{-- ===================================================== --}}
{{-- CHART.JS --}}
{{-- ===================================================== --}}

@if($trendResults->count())

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>

        const trendLabels = @json($trendLabels);
        const trendValues = @json($trendValues);

        const ctx = document
            .getElementById('vibrationTrendChart')
            .getContext('2d');


        new Chart(ctx, {

            type: 'line',

            data: {

                labels: trendLabels,

                datasets: [

                    {
                        label: 'Overall Velocity (mm/s RMS)',

                        data: trendValues,

                        borderWidth: 2,

                        tension: 0.25,

                        fill: false,

                        pointRadius: 4,

                        pointHoverRadius: 6
                    }

                ]

            },


            options: {

                responsive: true,

                maintainAspectRatio: false,

                interaction: {
                    intersect: false,
                    mode: 'index'
                },

                plugins: {

                    legend: {
                        display: true
                    }

                },

                scales: {

                    y: {

                        beginAtZero: true,

                        title: {
                            display: true,
                            text: 'mm/s RMS'
                        }

                    },

                    x: {

                        title: {
                            display: true,
                            text: 'Measurement Date'
                        }

                    }

                }

            }

        });

    </script>

@endif

@endsection