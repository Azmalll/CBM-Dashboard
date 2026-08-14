@extends('layouts.app')

@section('title', 'Equipment Inspection')

@section('content')

<div class="max-w-7xl mx-auto px-6 lg:px-8 py-8 space-y-8">

    {{-- =====================================================
         HEADER
    ====================================================== --}}

    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

        <div>
            <h1 class="text-3xl font-bold text-[#0F2D5C]">
                Equipment Inspection
            </h1>

            <p class="text-gray-500 mt-1">
                Equipment inspection detail
            </p>
        </div>


        <div class="flex flex-wrap gap-3">

            {{-- Back --}}
            <a
                href="{{ route('inspection.show', $equipmentInspection->inspection_id) }}"
                class="bg-gray-500 hover:bg-gray-600 text-white
                       px-5 py-3 rounded-xl font-semibold">

                ← Back

            </a>


            {{-- Vibration Trend --}}
            <a
                href="{{ route('equipment-inspection.trend', $equipmentInspection->id) }}"
                class="bg-[#0F2D5C] hover:bg-blue-900 text-white
                       px-5 py-3 rounded-xl font-semibold">

                📈 Vibration Trend

            </a>


            {{-- Add Measurement --}}
            <a
                href="{{ route('equipment-inspection.measurement.create', $equipmentInspection->id) }}"
                class="bg-[#0F2D5C] hover:bg-blue-900 text-white
                       px-5 py-3 rounded-xl font-semibold">

                + Add Measurement

            </a>

        </div>

    </div>


    {{-- =====================================================
         SUCCESS MESSAGE
    ====================================================== --}}

    @if(session('success'))

        <div class="bg-green-50 border border-green-200
                    text-green-700 px-6 py-4 rounded-xl">

            {{ session('success') }}

        </div>

    @endif


    {{-- =====================================================
         EQUIPMENT INFORMATION
    ====================================================== --}}

    <div class="bg-white rounded-2xl shadow-sm p-8">

        <h2 class="text-2xl font-bold text-[#0F2D5C] mb-6">
            Equipment Information
        </h2>


        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            {{-- Equipment --}}
            <div>

                <p class="text-gray-500">
                    Equipment
                </p>

                <p class="text-lg font-semibold">
                    {{ $equipmentInspection->equipment->equipment_name }}
                </p>

            </div>


            {{-- Inspection Date --}}
            <div>

                <p class="text-gray-500">
                    Inspection Date
                </p>

                <p class="text-lg font-semibold">
                    {{ $equipmentInspection->inspection->inspection_date }}
                </p>

            </div>


            {{-- Inspector --}}
            <div>

                <p class="text-gray-500">
                    Inspector
                </p>

                <p class="text-lg font-semibold">
                    {{ $equipmentInspection->inspection->inspector }}
                </p>

            </div>

        </div>

    </div>


    {{-- =====================================================
         ANALYSIS SUMMARY
    ====================================================== --}}

    <div class="bg-white rounded-2xl shadow-sm p-8">

        <div class="flex flex-col md:flex-row
                    md:items-center md:justify-between gap-4 mb-8">

            <h2 class="text-2xl font-bold text-[#0F2D5C]">
                Analysis Summary
            </h2>


            {{-- Edit Analysis --}}
            <a
                href="{{ route('equipment-inspection.edit', $equipmentInspection->id) }}"
                class="bg-white border border-[#0F2D5C]
                       text-[#0F2D5C] hover:bg-blue-50
                       px-5 py-3 rounded-xl font-semibold">

                Edit Analysis

            </a>

        </div>


        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

            {{-- Highest Overall --}}
            <div>

                <p class="text-gray-500">
                    Highest Overall
                </p>

                <p class="text-2xl font-bold">

                    {{ number_format((float) $equipmentInspection->highest_overall, 2) }}

                    <span class="text-sm font-normal text-gray-500">
                        mm/s RMS
                    </span>

                </p>

            </div>


            {{-- Highest Point --}}
            <div>

                <p class="text-gray-500">
                    Highest Point
                </p>

                <p class="text-xl font-semibold">

                    @if($equipmentInspection->highestPoint)

                        {{ $equipmentInspection->highestPoint->point_name }}

                    @else

                        -

                    @endif

                </p>

            </div>


            {{-- Severity --}}
            <div>

                <p class="text-gray-500">
                    Severity
                </p>

                <p class="text-xl font-semibold">

                    {{ $equipmentInspection->severity ?? 'Pending' }}

                </p>

            </div>


            {{-- Diagnosis --}}
            <div>

                <p class="text-gray-500">
                    Diagnosis
                </p>

                <p class="text-xl font-semibold">

                    {{ $equipmentInspection->diagnosis ?? '-' }}

                </p>

            </div>

        </div>


        {{-- Recommendation --}}
        <div class="mt-8 border-t pt-6">

            <p class="text-gray-500 mb-2">
                Recommendation
            </p>

            <div class="bg-gray-50 rounded-xl p-5">

                @if($equipmentInspection->recommendation)

                    <p class="whitespace-pre-line">
                        {{ $equipmentInspection->recommendation }}
                    </p>

                @else

                    <p class="text-gray-400">
                        No recommendation provided.
                    </p>

                @endif

            </div>

        </div>


        {{-- Report PDF --}}
        <div class="mt-6 border-t pt-6">

            <p class="text-gray-500 mb-3">
                Report PDF
            </p>

            @if($equipmentInspection->report_file)

                <a
                    href="{{ route('equipment-inspection.report', $equipmentInspection->id) }}"
                    target="_blank"
                    class="inline-block bg-red-600 hover:bg-red-700
                           text-white px-5 py-3 rounded-xl font-semibold">

                    View Report PDF

                </a>

            @else

                <p class="text-gray-400">
                    No report uploaded.
                </p>

            @endif

        </div>

    </div>


    {{-- =====================================================
         MEASUREMENT RESULTS
    ====================================================== --}}

    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">

        <div class="p-8">

            <h2 class="text-2xl font-bold text-[#0F2D5C]">
                Measurement Results
            </h2>

            <p class="text-gray-500 mt-1">
                Vibration measurement results for this equipment
            </p>

        </div>


        @if($equipmentInspection->measurementResults->count())

            <div class="overflow-x-auto">

                <table class="w-full">

                    <thead class="bg-gray-100">

                        <tr>

                            <th class="px-6 py-4 text-left">
                                No
                            </th>

                            <th class="px-6 py-4 text-left">
                                Measurement Point
                            </th>

                            <th class="px-6 py-4 text-left">
                                Overall
                            </th>

                            <th class="px-6 py-4 text-left">
                                Unit
                            </th>

                            <th class="px-6 py-4 text-left">
                                Peak
                            </th>

                            <th class="px-6 py-4 text-left">
                                Crest Factor
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @foreach($equipmentInspection->measurementResults as $index => $result)

                            <tr class="border-t">

                                <td class="px-6 py-4">
                                    {{ $index + 1 }}
                                </td>


                                <td class="px-6 py-4">

                                    @if($result->measurementPoint)

                                        <span class="font-semibold">
                                            {{ $result->measurementPoint->point_name }}
                                        </span>

                                        @if($result->measurementPoint->direction)

                                            <span class="text-gray-500">
                                                - {{ $result->measurementPoint->direction }}
                                            </span>

                                        @endif

                                    @else

                                        -

                                    @endif

                                </td>


                                <td class="px-6 py-4">

                                    {{ number_format((float) $result->overall_velocity, 3) }}

                                </td>


                                <td class="px-6 py-4">

                                    {{ $result->unit }}

                                </td>


                                <td class="px-6 py-4">

                                    {{ $result->peak_value !== null
                                        ? number_format((float) $result->peak_value, 3)
                                        : '-' }}

                                </td>


                                <td class="px-6 py-4">

                                    {{ $result->crest_factor !== null
                                        ? number_format((float) $result->crest_factor, 3)
                                        : '-' }}

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        @else

            <div class="px-8 pb-8">

                <div class="bg-gray-50 rounded-xl p-6
                            text-center text-gray-500">

                    No measurement results have been added yet.

                </div>

            </div>

        @endif

    </div>

</div>

@endsection