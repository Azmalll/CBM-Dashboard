@extends('layouts.app')

@section('title', 'Measurement Result')

@section('content')

<div class="max-w-5xl mx-auto px-6 py-8">

    {{-- ========================================================= --}}
    {{-- HEADER --}}
    {{-- ========================================================= --}}
    <div class="flex items-center justify-between mb-8">

        <div>
            <h1 class="text-3xl font-bold text-[#0F2D5C]">
                Measurement Result
            </h1>

            <p class="text-gray-500 mt-1">
                Vibration measurement detail
            </p>
        </div>

        <div class="flex items-center gap-3">

            <a
                href="{{ route('measurement-result.index') }}"
                class="bg-gray-200 hover:bg-gray-300
                       text-gray-700
                       px-5 py-3
                       rounded-xl
                       font-medium
                       transition"
            >
                ← Back
            </a>

            @if(auth()->user()?->isAdmin())
                <a
                    href="{{ route(
                        'measurement-result.edit',
                        $measurementResult->id
                    ) }}"
                    class="bg-[#0F2D5C] hover:bg-blue-900
                           text-white
                           px-5 py-3
                           rounded-xl
                           font-medium
                           transition"
                >
                    Edit
                </a>
            @endif

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- RELATION DATA --}}
    {{-- ========================================================= --}}
    @php

        /*
        |--------------------------------------------------------------------------
        | EQUIPMENT
        |--------------------------------------------------------------------------
        | Prioritas:
        | 1. Measurement Point -> Equipment
        | 2. Equipment Inspection -> Equipment
        |
        | Ini memastikan equipment yang tampil sama dengan equipment
        | pada halaman Measurement Result yang diklik sebelumnya.
        |--------------------------------------------------------------------------
        */

        $equipment =
            $measurementResult->measurementPoint?->equipment
            ?? $measurementResult->equipmentInspection?->equipment;

        $equipmentName =
            $equipment?->equipment_name ?? '-';

        $measurementPoint =
            $measurementResult->measurementPoint?->point_name
            ?? '-';

        $measurementDate =
            $measurementResult->measurement_datetime
                ? $measurementResult->measurement_datetime->format('d M Y')
                : (
                    $measurementResult->measurement_date
                        ? $measurementResult->measurement_date->format('d M Y')
                        : '-'
                );

        $measurementTime =
            $measurementResult->measurement_datetime
                ? $measurementResult->measurement_datetime->format('H:i:s')
                : '-';

        $inspector =
            $measurementResult->inspector
            ?? $measurementResult->inspection?->inspector
            ?? '-';

        $inspectionDate =
            $measurementResult->inspection?->inspection_date
            ? \Carbon\Carbon::parse(
                $measurementResult->inspection->inspection_date
            )->format('d M Y')
            : '-';

        $overallUnit =
            $measurementResult->unit ?? 'mm/s RMS';

        /*
        |--------------------------------------------------------------------------
        | PEAK UNIT
        |--------------------------------------------------------------------------
        | Overall menggunakan RMS.
        | Peak ditampilkan sebagai Peak, bukan RMS.
        |--------------------------------------------------------------------------
        */

        $peakUnit = match ($overallUnit) {
            'inch/s RMS' => 'inch/s Peak',
            default => 'mm/s Peak',
        };

        $severity =
            $measurementResult->equipmentInspection?->severity
            ?? 'Pending';

        $severityClass = match ($severity) {
            'Normal' => 'bg-green-100 text-green-700',
            'Alert' => 'bg-yellow-100 text-yellow-700',
            'Danger', 'Alarm' => 'bg-orange-100 text-orange-700',
            'Critical', 'Fault' => 'bg-red-100 text-red-700',
            default => 'bg-gray-100 text-gray-600',
        };

    @endphp


    {{-- ========================================================= --}}
    {{-- MEASUREMENT INFORMATION --}}
    {{-- ========================================================= --}}
    <div class="bg-white rounded-2xl shadow-sm p-6 md:p-7 mb-6">

        <h2 class="text-xl font-bold text-[#0F2D5C] mb-6">
            Measurement Information
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-x-8 gap-y-6">

            {{-- MEASUREMENT DATE --}}
            <div>
                <p class="text-sm text-gray-500">
                    Measurement Date
                </p>

                <p class="font-semibold text-gray-800 mt-1">
                    {{ $measurementDate }}
                </p>
            </div>


            {{-- MEASUREMENT TIME --}}
            <div>
                <p class="text-sm text-gray-500">
                    Measurement Time
                </p>

                <p class="font-semibold text-gray-800 mt-1">
                    {{ $measurementTime }}
                </p>
            </div>


            {{-- INSPECTOR --}}
            <div>
                <p class="text-sm text-gray-500">
                    Inspector
                </p>

                <p class="font-semibold text-gray-800 mt-1">
                    {{ $inspector }}
                </p>
            </div>


            {{-- EQUIPMENT --}}
            <div>
                <p class="text-sm text-gray-500">
                    Equipment
                </p>

                <p class="font-semibold text-gray-800 mt-1">
                    {{ $equipmentName }}
                </p>
            </div>


            {{-- MEASUREMENT POINT --}}
            <div>
                <p class="text-sm text-gray-500">
                    Measurement Point
                </p>

                <p class="font-semibold text-gray-800 mt-1">
                    {{ $measurementPoint }}

                    @if($measurementResult->measurementPoint?->direction)
                        <span class="text-gray-500 font-normal">
                            - {{ $measurementResult->measurementPoint->direction }}
                        </span>
                    @endif
                </p>
            </div>


            {{-- INSPECTION SESSION --}}
            <div>
                <p class="text-sm text-gray-500">
                    Inspection Session
                </p>

                <p class="font-semibold text-gray-800 mt-1">
                    {{ $inspectionDate }}
                </p>
            </div>


            {{-- SEVERITY --}}
            <div>
                <p class="text-sm text-gray-500">
                    Severity
                </p>

                <div class="mt-2">
                    <span
                        class="inline-flex items-center
                               px-3 py-1.5
                               rounded-full
                               text-sm
                               font-semibold
                               {{ $severityClass }}"
                    >
                        {{ $severity }}
                    </span>
                </div>
            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- VIBRATION DATA --}}
    {{-- ========================================================= --}}
    <div class="bg-white rounded-2xl shadow-sm p-6 md:p-7">

        <h2 class="text-xl font-bold text-[#0F2D5C] mb-6">
            Vibration Data
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

            {{-- OVERALL VELOCITY --}}
            <div class="bg-gray-50 rounded-xl p-5">

                <p class="text-gray-500 text-sm mb-2">
                    Overall Velocity
                </p>

                <div class="flex items-baseline gap-2">

                    <span class="text-3xl font-bold text-[#0F2D5C]">
                        {{ $measurementResult->overall_velocity !== null
                            ? number_format(
                                (float) $measurementResult->overall_velocity,
                                3
                            )
                            : '-' }}
                    </span>

                    @if($measurementResult->overall_velocity !== null)
                        <span class="text-sm text-gray-500">
                            {{ $overallUnit }}
                        </span>
                    @endif

                </div>

            </div>


            {{-- PEAK VALUE --}}
            <div class="bg-gray-50 rounded-xl p-5">

                <p class="text-gray-500 text-sm mb-2">
                    Peak Value
                </p>

                <div class="flex items-baseline gap-2">

                    <span class="text-3xl font-bold text-[#0F2D5C]">
                        {{ $measurementResult->peak_value !== null
                            ? number_format(
                                (float) $measurementResult->peak_value,
                                3
                            )
                            : '-' }}
                    </span>

                    @if($measurementResult->peak_value !== null)
                        <span class="text-sm text-gray-500">
                            {{ $peakUnit }}
                        </span>
                    @endif

                </div>

            </div>


            {{-- CREST FACTOR --}}
            <div class="bg-gray-50 rounded-xl p-5">

                <p class="text-gray-500 text-sm mb-2">
                    Crest Factor
                </p>

                <span class="text-3xl font-bold text-[#0F2D5C]">
                    {{ $measurementResult->crest_factor !== null
                        ? number_format(
                            (float) $measurementResult->crest_factor,
                            3
                        )
                        : '-' }}
                </span>

            </div>

        </div>

    </div>

</div>

@endsection
