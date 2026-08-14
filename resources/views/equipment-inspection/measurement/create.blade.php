@extends('layouts.app')

@section('content')

<div class="max-w-5xl mx-auto px-6 py-10">

    <div class="flex items-center justify-between mb-8">

        <div>
            <h1 class="text-3xl font-bold text-blue-950">
                Add Measurement Result
            </h1>

            <p class="text-gray-500 mt-1">
                Input vibration measurement
            </p>
        </div>

        <a
            href="{{ route('inspection.show', $equipmentInspection->inspection_id) }}"
            class="px-6 py-3 rounded-xl bg-gray-500 text-white hover:bg-gray-600"
        >
            ← Back
        </a>

    </div>


    <div class="bg-white rounded-2xl shadow p-8">

        <div class="mb-8">

            <label class="block font-semibold text-gray-700 mb-2">
                Equipment
            </label>

            <div class="w-full border border-gray-300 rounded-xl px-4 py-3 bg-gray-100">
                {{ $equipmentInspection->equipment->equipment_name }}
            </div>

        </div>


        <form
            method="POST"
            action="{{ route(
                'equipment-inspection.measurement.store',
                $equipmentInspection
            ) }}"
        >

            @csrf


            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">


                <!-- Measurement Point -->

                <div>

                    <label class="block font-semibold text-gray-700 mb-2">
                        Measurement Point
                    </label>

                    <select
                        name="measurement_point_id"
                        required
                        class="w-full border border-gray-300 rounded-xl px-4 py-3"
                    >

                        <option value="">
                            -- Select Measurement Point --
                        </option>

                        @foreach($measurementPoints as $point)

                            <option
                                value="{{ $point->id }}"
                                {{ old('measurement_point_id') == $point->id ? 'selected' : '' }}
                            >
                                {{ $point->point_name }}
                            </option>

                        @endforeach

                    </select>

                    @error('measurement_point_id')
                        <p class="text-red-500 text-sm mt-1">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                <!-- Unit -->

                <div>

                    <label class="block font-semibold text-gray-700 mb-2">
                        Unit
                    </label>

                    <select
                        name="unit"
                        required
                        class="w-full border border-gray-300 rounded-xl px-4 py-3"
                    >

                        <option value="mm/s RMS">
                            mm/s RMS
                        </option>

                        <option value="mm/s Peak">
                            mm/s Peak
                        </option>

                    </select>

                </div>


                <!-- Overall Velocity -->

                <div>

                    <label class="block font-semibold text-gray-700 mb-2">
                        Overall Velocity
                    </label>

                    <input
                        type="number"
                        name="overall_velocity"
                        value="{{ old('overall_velocity') }}"
                        step="0.01"
                        min="0"
                        required
                        class="w-full border border-gray-300 rounded-xl px-4 py-3"
                        placeholder="Example: 2.35"
                    >

                    <p class="text-sm text-gray-500 mt-1">
                        Input dalam mm/s RMS
                    </p>

                    @error('overall_velocity')
                        <p class="text-red-500 text-sm mt-1">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                <!-- Peak Value -->

                <div>

                    <label class="block font-semibold text-gray-700 mb-2">
                        Peak Value
                    </label>

                    <input
                        type="number"
                        name="peak_value"
                        value="{{ old('peak_value') }}"
                        step="0.01"
                        min="0"
                        class="w-full border border-gray-300 rounded-xl px-4 py-3"
                        placeholder="Example: 3.45"
                    >

                </div>


                <!-- Crest Factor -->

                <div>

                    <label class="block font-semibold text-gray-700 mb-2">
                        Crest Factor
                    </label>

                    <input
                        type="number"
                        name="crest_factor"
                        value="{{ old('crest_factor') }}"
                        step="0.01"
                        min="0"
                        class="w-full border border-gray-300 rounded-xl px-4 py-3"
                        placeholder="Example: 1.80"
                    >

                </div>

            </div>


            <div class="mt-8">

                <button
                    type="submit"
                    class="px-7 py-3 rounded-xl bg-blue-900 text-white font-semibold hover:bg-blue-800"
                >
                    Save Measurement Result
                </button>

            </div>

        </form>

    </div>

</div>

@endsection