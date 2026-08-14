@extends('layouts.app')

@section('title', 'Edit Measurement Result')

@section('content')

<div class="max-w-5xl mx-auto px-6 py-8">

    {{-- HEADER --}}
    <div class="flex items-center justify-between mb-8">

        <div>
            <h1 class="text-3xl font-bold text-[#0F2D5C]">
                Edit Measurement Result
            </h1>

            <p class="text-gray-500 mt-1">
                Update Vibration Measurement
            </p>
        </div>

        <a
    href="{{ route('measurement-result.index') }}"
    class="bg-gray-600 hover:bg-gray-700
           text-white
           px-5 py-3
           rounded-xl
           font-medium
           transition"
>
    ← Back
</a>

    </div>


    {{-- ERROR MESSAGE --}}
    @if($errors->any())

        <div class="mb-6 bg-red-100 border border-red-300
                    text-red-700 px-5 py-4 rounded-xl">

            <ul class="list-disc ml-5">

                @foreach($errors->all() as $error)

                    <li>{{ $error }}</li>

                @endforeach

            </ul>

        </div>

    @endif


    {{-- EQUIPMENT INFORMATION --}}
    <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">

        <h2 class="text-xl font-bold text-[#0F2D5C] mb-5">
            Equipment Information
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <div>
                <p class="text-sm text-gray-500">
                    Equipment
                </p>

                <p class="font-semibold text-gray-800 mt-1">
                    {{ $measurementResult->measurementPoint?->equipment?->equipment_name ?? '-' }}
                </p>
            </div>

            <div>
                <p class="text-sm text-gray-500">
                    Measurement Date & Time
                </p>

                <p class="font-semibold text-gray-800 mt-1">
                    {{ $measurementResult->measurement_datetime?->format('d-m-Y H:i:s') ?? '-' }}
                </p>
            </div>

            <div>
                <p class="text-sm text-gray-500">
                    Measurement ID
                </p>

                <p class="font-semibold text-gray-800 mt-1">
                    #{{ $measurementResult->id }}
                </p>
            </div>

        </div>

    </div>


    {{-- FORM --}}
    <div class="bg-white rounded-2xl shadow-sm p-8">

        <form
            method="POST"
            action="{{ route(
                'measurement-result.update',
                $measurementResult->id
            ) }}"
        >

            @csrf
            @method('PUT')


            {{-- MEASUREMENT DATE & TIME --}}
            <div class="mb-6">

                <label
                    for="measurement_datetime"
                    class="block text-sm font-semibold text-gray-700 mb-2"
                >
                    Measurement Date & Time
                </label>

                <input
                    type="datetime-local"
                    id="measurement_datetime"
                    name="measurement_datetime"
                    value="{{ old(
                        'measurement_datetime',
                        $measurementResult->measurement_datetime?->format('Y-m-d\TH:i')
                    ) }}"
                    required
                    class="w-full border border-gray-300
                           rounded-xl px-4 py-3
                           focus:outline-none
                           focus:ring-2
                           focus:ring-blue-500"
                >

                @error('measurement_datetime')

                    <p class="text-red-600 text-sm mt-1">
                        {{ $message }}
                    </p>

                @enderror

            </div>


            {{-- INSPECTOR --}}
            <div class="mb-6">

                <label
                    for="inspector"
                    class="block text-sm font-semibold text-gray-700 mb-2"
                >
                    Inspector
                </label>

                <input
                    type="text"
                    id="inspector"
                    name="inspector"
                    value="{{ old(
                        'inspector',
                        $measurementResult->inspector
                    ) }}"
                    maxlength="255"
                    placeholder="Contoh: Azmal"
                    class="w-full border border-gray-300
                           rounded-xl px-4 py-3
                           focus:outline-none
                           focus:ring-2
                           focus:ring-blue-500"
                >

                <p class="text-xs text-gray-500 mt-2">
                    Inspector dapat diubah secara individual.
                </p>

                @error('inspector')

                    <p class="text-red-600 text-sm mt-1">
                        {{ $message }}
                    </p>

                @enderror

            </div>


            {{-- MEASUREMENT POINT --}}
            <div class="mb-6">

                <label
                    for="measurement_point_id"
                    class="block text-sm font-semibold text-gray-700 mb-2"
                >
                    Measurement Point
                </label>

                <select
                    id="measurement_point_id"
                    name="measurement_point_id"
                    required
                    class="w-full border border-gray-300
                           rounded-xl px-4 py-3
                           focus:outline-none
                           focus:ring-2
                           focus:ring-blue-500"
                >

                    @foreach($measurementPoints as $point)

                        <option
                            value="{{ $point->id }}"
                            {{ old(
                                'measurement_point_id',
                                $measurementResult->measurement_point_id
                            ) == $point->id ? 'selected' : '' }}
                        >
                            {{ $point->point_name }}
                        </option>

                    @endforeach

                </select>

                @error('measurement_point_id')

                    <p class="text-red-600 text-sm mt-1">
                        {{ $message }}
                    </p>

                @enderror

            </div>


            {{-- OVERALL VELOCITY --}}
            <div class="mb-6">

                <label
                    for="overall_velocity"
                    class="block text-sm font-semibold text-gray-700 mb-2"
                >
                    Overall Velocity
                </label>

                <input
                    type="number"
                    id="overall_velocity"
                    name="overall_velocity"
                    value="{{ old(
                        'overall_velocity',
                        $measurementResult->overall_velocity
                    ) }}"
                    step="0.001"
                    min="0"
                    required
                    class="w-full border border-gray-300
                           rounded-xl px-4 py-3
                           focus:outline-none
                           focus:ring-2
                           focus:ring-blue-500"
                >

                @error('overall_velocity')

                    <p class="text-red-600 text-sm mt-1">
                        {{ $message }}
                    </p>

                @enderror

            </div>


            {{-- UNIT --}}
            <div class="mb-6">

                <label
                    for="unit"
                    class="block text-sm font-semibold text-gray-700 mb-2"
                >
                    Unit
                </label>

                <select
                    id="unit"
                    name="unit"
                    required
                    class="w-full border border-gray-300
                           rounded-xl px-4 py-3"
                >

                    <option
                        value="mm/s RMS"
                        {{ old(
                            'unit',
                            $measurementResult->unit
                        ) === 'mm/s RMS' ? 'selected' : '' }}
                    >
                        mm/s RMS
                    </option>

                    <option
                        value="inch/s RMS"
                        {{ old(
                            'unit',
                            $measurementResult->unit
                        ) === 'inch/s RMS' ? 'selected' : '' }}
                    >
                        inch/s RMS
                    </option>

                </select>

                @error('unit')

                    <p class="text-red-600 text-sm mt-1">
                        {{ $message }}
                    </p>

                @enderror

            </div>


            {{-- PEAK VALUE --}}
            <div class="mb-6">

                <label
                    for="peak_value"
                    class="block text-sm font-semibold text-gray-700 mb-2"
                >
                    Peak Value
                </label>

                <input
                    type="number"
                    id="peak_value"
                    name="peak_value"
                    value="{{ old(
                        'peak_value',
                        $measurementResult->peak_value
                    ) }}"
                    step="0.001"
                    min="0"
                    placeholder="Optional"
                    class="w-full border border-gray-300
                           rounded-xl px-4 py-3"
                >

                @error('peak_value')

                    <p class="text-red-600 text-sm mt-1">
                        {{ $message }}
                    </p>

                @enderror

            </div>


            {{-- CREST FACTOR --}}
            <div class="mb-8">

                <label
                    for="crest_factor"
                    class="block text-sm font-semibold text-gray-700 mb-2"
                >
                    Crest Factor
                </label>

                <input
                    type="number"
                    id="crest_factor"
                    name="crest_factor"
                    value="{{ old(
                        'crest_factor',
                        $measurementResult->crest_factor
                    ) }}"
                    step="0.001"
                    min="0"
                    placeholder="Optional"
                    class="w-full border border-gray-300
                           rounded-xl px-4 py-3"
                >

                @error('crest_factor')

                    <p class="text-red-600 text-sm mt-1">
                        {{ $message }}
                    </p>

                @enderror

            </div>


            {{-- ACTION --}}
            <div class="flex justify-end gap-3">

                <a
                    href="{{ route(
                        'equipment-inspection.show',
                        $measurementResult->equipment_inspection_id
                    ) }}"
                    class="bg-gray-500 hover:bg-gray-600
                           text-white px-6 py-3
                           rounded-xl"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="bg-[#0F2D5C] hover:bg-blue-900
                           text-white px-6 py-3
                           rounded-xl font-semibold"
                >
                    Update Measurement
                </button>

            </div>

        </form>

    </div>

</div>

@endsection