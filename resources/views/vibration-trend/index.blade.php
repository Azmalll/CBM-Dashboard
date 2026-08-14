@extends('layouts.app')

@section('title', 'Vibration Trend')

@section('content')

<div class="flex justify-between items-center mb-8">

    <div>

        <h1 class="text-3xl font-bold text-[#0F2D5C]">
            Vibration Trend
        </h1>

        <p class="text-gray-500 mt-1">
            Historical vibration monitoring
        </p>

    </div>


    <div class="flex gap-3">

        <a
            href="{{ route(
                'equipment-inspection.show',
                $equipmentInspection->id
            ) }}"
            class="bg-gray-500 hover:bg-gray-600 text-white px-5 py-3 rounded-xl">

            ← Back

        </a>

    </div>

</div>



{{-- ===================================================== --}}
{{-- EQUIPMENT INFORMATION --}}
{{-- ===================================================== --}}

<div class="bg-white rounded-2xl shadow p-6 mb-6">

    <h2 class="text-xl font-bold text-[#0F2D5C] mb-5">
        Equipment Information
    </h2>


    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">


        <div>

            <p class="text-gray-500 text-sm">
                Equipment
            </p>

            <p class="font-semibold text-lg mt-1">
                {{ $equipmentInspection->equipment->equipment_name ?? '-' }}
            </p>

        </div>


        <div>

            <p class="text-gray-500 text-sm">
                Inspection Date
            </p>

            <p class="font-semibold text-lg mt-1">
                {{ $equipmentInspection->inspection->inspection_date ?? '-' }}
            </p>

        </div>


        <div>

            <p class="text-gray-500 text-sm">
                Current Severity
            </p>

            <p class="font-semibold text-lg mt-1">
                {{ $equipmentInspection->severity ?? 'Pending' }}
            </p>

        </div>


    </div>

</div>



{{-- ===================================================== --}}
{{-- MEASUREMENT POINT FILTER --}}
{{-- ===================================================== --}}

<div class="bg-white rounded-2xl shadow p-6 mb-6">

    <form
        method="GET"
        action="{{ route(
            'equipment-inspection.trend',
            $equipmentInspection->id
        ) }}">

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">


            <div class="md:col-span-2">

                <label class="font-semibold">
                    Measurement Point
                </label>

                <select
                    name="measurement_point_id"
                    class="w-full mt-2 border rounded-xl p-3"
                    onchange="this.form.submit()">

                    @foreach($measurementPoints as $point)

                        <option
                            value="{{ $point->id }}"
                            {{ (int) $selectedPointId === (int) $point->id
                                ? 'selected'
                                : ''
                            }}>

                            {{ $point->point_name }}
                            -
                            {{ $point->direction }}

                        </option>

                    @endforeach

                </select>

            </div>


            <div>

                <p class="text-gray-500 text-sm">
                    Selected Point
                </p>

                <p class="font-semibold mt-1">

                    @if($selectedPoint)

                        {{ $selectedPoint->point_name }}
                        -
                        {{ $selectedPoint->direction }}

                    @else

                        -

                    @endif

                </p>

            </div>


        </div>

    </form>

</div>



{{-- ===================================================== --}}
{{-- TREND CHART --}}
{{-- ===================================================== --}}

<div class="bg-white rounded-2xl shadow p-6 mb-6">

    <div class="mb-6">

        <h2 class="text-xl font-bold text-[#0F2D5C]">
            Overall Vibration Trend
        </h2>

        <p class="text-gray-500 text-sm mt-1">
            Overall velocity trend in mm/s RMS
        </p>

    </div>


    @if($results->count() > 0)

        <div class="relative h-[350px]">

            <canvas id="vibrationTrendChart"></canvas>

        </div>

    @else

        <div class="py-16 text-center text-gray-500">

            Belum ada historical measurement
            untuk measurement point ini.

        </div>

    @endif

</div>



{{-- ===================================================== --}}
{{-- MEASUREMENT HISTORY --}}
{{-- ===================================================== --}}

<div class="bg-white rounded-2xl shadow overflow-hidden">

    <div class="p-6 border-b">

        <h2 class="text-xl font-bold text-[#0F2D5C]">
            Measurement History
        </h2>

        <p class="text-gray-500 text-sm mt-1">
            Historical measurement records
        </p>

    </div>


    <div class="overflow-x-auto">

        <table class="w-full">

            <thead class="bg-gray-50">

                <tr>

                    <th class="text-left px-6 py-4">
                        Date
                    </th>

                    <th class="text-left px-6 py-4">
                        Measurement Point
                    </th>

                    <th class="text-left px-6 py-4">
                        Overall
                    </th>

                    <th class="text-left px-6 py-4">
                        Peak
                    </th>

                    <th class="text-left px-6 py-4">
                        Crest Factor
                    </th>

                </tr>

            </thead>


            <tbody class="divide-y">


                @forelse($results->sortByDesc('created_at') as $result)

                    <tr class="hover:bg-gray-50">


                        <td class="px-6 py-4">

                            {{ $result->created_at
                                ? $result->created_at->format('d M Y H:i')
                                : '-'
                            }}

                        </td>


                        <td class="px-6 py-4 font-semibold">

                            {{ $result->measurementPoint->point_name ?? '-' }}

                            @if($result->measurementPoint)

                                <span class="text-gray-400">
                                    -
                                </span>

                                {{ $result->measurementPoint->direction }}

                            @endif

                        </td>


                        <td class="px-6 py-4">

                            <span class="font-semibold">

                                {{ number_format(
                                    (float) $result->overall_velocity,
                                    3
                                ) }}

                            </span>

                            <span class="text-gray-500 text-sm">

                                {{ $result->unit }}

                            </span>

                        </td>


                        <td class="px-6 py-4">

                            {{ $result->peak_value !== null
                                ? number_format(
                                    (float) $result->peak_value,
                                    3
                                )
                                : '-'
                            }}

                        </td>


                        <td class="px-6 py-4">

                            {{ $result->crest_factor !== null
                                ? number_format(
                                    (float) $result->crest_factor,
                                    3
                                )
                                : '-'
                            }}

                        </td>


                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="5"
                            class="px-6 py-10 text-center text-gray-500">

                            Belum ada data measurement.

                        </td>

                    </tr>

                @endforelse


            </tbody>

        </table>

    </div>

</div>



{{-- ===================================================== --}}
{{-- CHART.JS --}}
{{-- ===================================================== --}}

@if($results->count() > 0)

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

    const labels = @json($chartLabels);

    const values = @json($chartValues);


    const ctx = document
        .getElementById('vibrationTrendChart')
        .getContext('2d');


    new Chart(ctx, {

        type: 'line',

        data: {

            labels: labels,

            datasets: [

                {

                    label: 'Overall Velocity (mm/s RMS)',

                    data: values,

                    borderWidth: 3,

                    tension: 0.3,

                    fill: false,

                    pointRadius: 5,

                    pointHoverRadius: 7

                }

            ]

        },


        options: {

            responsive: true,

            maintainAspectRatio: false,

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

            },


            plugins: {

                legend: {

                    display: true

                }

            }

        }

    });

</script>

@endif

@endsection