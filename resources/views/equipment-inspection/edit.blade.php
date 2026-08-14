@extends('layouts.app')

@section('title', 'Edit Equipment Analysis')

@section('content')

<div class="flex justify-between items-center mb-8">

    <div>
        <h1 class="text-3xl font-bold text-[#0F2D5C]">
            Edit Equipment Analysis
        </h1>

        <p class="text-gray-500">
            Manual vibration analysis result
        </p>
    </div>

    <a
    href="{{ route('dashboard') }}"
    class="bg-gray-600 hover:bg-gray-700 text-white px-5 py-3 rounded-xl font-medium transition"
>
    ← Back
</a>

</div>


<div class="bg-white rounded-2xl shadow p-8">

    <form action="{{ route('equipment-inspection.update', $equipmentInspection->id) }}"
          method="POST"
          enctype="multipart/form-data">

        @csrf

        @method('PUT')


        {{-- Equipment Information --}}

        <div class="bg-gray-50 rounded-xl p-5 mb-8">

            <h2 class="text-lg font-bold text-[#0F2D5C] mb-4">
                Equipment Information
            </h2>

            <div class="grid grid-cols-3 gap-6">

                <div>

                    <p class="text-sm text-gray-500">
                        Equipment
                    </p>

                    <p class="font-semibold">
                        {{ $equipmentInspection->equipment->equipment_name }}
                    </p>

                </div>


                <div>

                    <p class="text-sm text-gray-500">
                        Inspection Date
                    </p>

                    <p class="font-semibold">
                        {{ $equipmentInspection->inspection->inspection_date }}
                    </p>

                </div>


                <div>

                    <p class="text-sm text-gray-500">
                        Inspector
                    </p>

                    <p class="font-semibold">
                        {{ $equipmentInspection->inspection->inspector }}
                    </p>

                </div>

            </div>

        </div>


        {{-- Highest Overall --}}

        <div class="mb-6">

            <label class="font-semibold">
                Highest Overall
            </label>

            <input
                type="number"
                name="highest_overall"
                step="0.01"
                min="0"
                value="{{ old('highest_overall', $equipmentInspection->highest_overall) }}"
                class="w-full mt-2 border rounded-xl p-3"
                required>

            <p class="text-sm text-gray-500 mt-1">
                Nilai overall vibration tertinggi dari hasil measurement.
            </p>

        </div>


        {{-- Highest Measurement Point --}}

        <div class="mb-6">

            <label class="font-semibold">
                Highest Measurement Point
            </label>

            <select
                name="highest_point_id"
                class="w-full mt-2 border rounded-xl p-3">

                <option value="">
                    -- Select Measurement Point --
                </option>

                @foreach($measurementPoints as $point)

                    <option
                        value="{{ $point->id }}"
                        {{ old('highest_point_id', $equipmentInspection->highest_point_id) == $point->id ? 'selected' : '' }}>

                        {{ $point->point_name }}
                        -
                        {{ $point->direction }}

                    </option>

                @endforeach

            </select>

        </div>


        {{-- Severity Manual --}}

        <div class="mb-6">

            <label class="font-semibold">
                Severity
            </label>

            <select
                name="severity"
                class="w-full mt-2 border rounded-xl p-3"
                required>

                <option value="">
                    -- Select Severity --
                </option>

                <option value="Normal"
                    {{ old('severity', $equipmentInspection->severity) == 'Normal' ? 'selected' : '' }}>
                    Normal
                </option>

                <option value="Alert"
                    {{ old('severity', $equipmentInspection->severity) == 'Alert' ? 'selected' : '' }}>
                    Alert
                </option>

                <option value="Danger"
                    {{ old('severity', $equipmentInspection->severity) == 'Danger' ? 'selected' : '' }}>
                    Danger
                </option>

                <option value="Critical"
                    {{ old('severity', $equipmentInspection->severity) == 'Critical' ? 'selected' : '' }}>
                    Critical
                </option>

            </select>

            <p class="text-sm text-gray-500 mt-1">
                Severity ditentukan manual oleh analyst.
            </p>

        </div>


        {{-- Diagnosis --}}

        <div class="mb-6">

            <label class="font-semibold">
                Diagnosis
            </label>

            <input
                type="text"
                name="diagnosis"
                value="{{ old('diagnosis', $equipmentInspection->diagnosis) }}"
                class="w-full mt-2 border rounded-xl p-3"
                placeholder="Contoh: Indication of imbalance">

        </div>


        {{-- Recommendation --}}

        <div class="mb-6">

            <label class="font-semibold">
                Recommendation
            </label>

            <textarea
                name="recommendation"
                rows="5"
                class="w-full mt-2 border rounded-xl p-3"
                placeholder="Enter maintenance recommendation...">{{ old('recommendation', $equipmentInspection->recommendation) }}</textarea>

        </div>


        {{-- Report PDF --}}

        <div class="mb-8">

            <label class="font-semibold">
                Report PDF
            </label>

            <input
                type="file"
                name="report_file"
                accept=".pdf"
                class="w-full mt-2 border rounded-xl p-3">

            <p class="text-sm text-gray-500 mt-1">
                Upload PDF hasil analysis yang dibuat manual oleh analyst.
            </p>

            @if($equipmentInspection->report_file)

                <p class="text-sm text-green-600 mt-2">
                    Report PDF sudah tersedia.
                </p>

            @endif

        </div>


        {{-- Save --}}

        <div class="flex gap-3">

            <a href="{{ route('equipment-inspection.show', $equipmentInspection->id) }}"
               class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-xl">

                Cancel

            </a>

            <button
                type="submit"
                class="bg-[#0F2D5C] hover:bg-blue-900 text-white px-6 py-3 rounded-xl">

                Save Analysis

            </button>

        </div>

    </form>

</div>

@endsection