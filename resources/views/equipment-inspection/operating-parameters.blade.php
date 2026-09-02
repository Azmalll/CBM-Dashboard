@extends('layouts.app')

@section('title', 'Edit Operating Parameters')

@section('content')

<div class="max-w-4xl mx-auto px-6 py-10">

    {{-- HEADER --}}
    <div class="flex items-center justify-between mb-8">

        <div>

            <h1 class="text-3xl font-bold text-[#0F2D5C]">
                Edit Operating Parameters
            </h1>

            <p class="text-gray-500 mt-1">
                {{ $equipmentInspection->equipment->equipment_name ?? '-' }}
                ·
                {{ $equipmentInspection->inspection->inspection_date ?? '-' }}
            </p>

        </div>


        <a
            href="{{ route(
                'equipment-inspection.show',
                $equipmentInspection->id
            ) }}"
            class="bg-gray-500 hover:bg-gray-600
                   text-white px-5 py-3
                   rounded-xl font-semibold"
        >
            ← Back
        </a>

    </div>


    {{-- VALIDATION ERROR --}}
    @if($errors->any())

        <div class="bg-red-50 border border-red-200
                    text-red-700 px-6 py-4
                    rounded-xl mb-6">

            <p class="font-semibold mb-2">
                Input tidak valid:
            </p>

            <ul class="list-disc list-inside">

                @foreach($errors->all() as $error)

                    <li>{{ $error }}</li>

                @endforeach

            </ul>

        </div>

    @endif


    {{-- FORM --}}
    <div class="bg-white rounded-2xl shadow-sm p-8">

        <form
            method="POST"
            action="{{ route(
                'equipment-inspection.operating-parameters.update',
                $equipmentInspection->id
            ) }}"
        >

            @csrf

            @method('PUT')


            @php
                $parameters =
                    $equipmentInspection->operating_parameters ?? [];
            @endphp


            {{-- SPEED --}}
            <div class="mb-6">

                <label
                    for="speed_rpm"
                    class="block font-semibold text-gray-700 mb-2"
                >
                    Speed / RPM
                </label>

                <div class="flex gap-3">

                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        id="speed_rpm"
                        name="speed_rpm"
                        value="{{ old(
                            'speed_rpm',
                            $parameters['speed_rpm'] ?? ''
                        ) }}"
                        class="w-full border border-gray-300
                               rounded-xl px-4 py-3"
                        placeholder="e.g. 1480"
                    >

                    <span class="flex items-center
                                 px-4 rounded-xl bg-gray-100
                                 text-gray-600 font-semibold">
                        RPM
                    </span>

                </div>

            </div>


            {{-- PRESSURE --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">

                <div>

                    <label
                        for="suction_pressure"
                        class="block font-semibold text-gray-700 mb-2"
                    >
                        Suction Pressure
                    </label>

                    <div class="flex gap-3">

                        <input
                            type="number"
                            step="0.01"
                            id="suction_pressure"
                            name="suction_pressure"
                            value="{{ old(
                                'suction_pressure',
                                $parameters['suction_pressure'] ?? ''
                            ) }}"
                            class="w-full border border-gray-300
                                   rounded-xl px-4 py-3"
                            placeholder="e.g. 8"
                        >

                        <span class="flex items-center
                                     px-4 rounded-xl bg-gray-100
                                     text-gray-600 font-semibold">
                            Psi
                        </span>

                    </div>

                </div>


                <div>

                    <label
                        for="discharge_pressure"
                        class="block font-semibold text-gray-700 mb-2"
                    >
                        Discharge Pressure
                    </label>

                    <div class="flex gap-3">

                        <input
                            type="number"
                            step="0.01"
                            id="discharge_pressure"
                            name="discharge_pressure"
                            value="{{ old(
                                'discharge_pressure',
                                $parameters['discharge_pressure'] ?? ''
                            ) }}"
                            class="w-full border border-gray-300
                                   rounded-xl px-4 py-3"
                            placeholder="e.g. 761"
                        >

                        <span class="flex items-center
                                     px-4 rounded-xl bg-gray-100
                                     text-gray-600 font-semibold">
                            Psi
                        </span>

                    </div>

                </div>

            </div>


            {{-- MOTOR BEARING TEMPERATURE --}}
            <div class="mb-6">

                <h2 class="text-lg font-bold text-[#0F2D5C] mb-4">
                    Motor Bearing Temperature
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div>

                        <label
                            for="bearing_temp_m_out"
                            class="block font-semibold text-gray-700 mb-2"
                        >
                            Bearing Temp M-Out
                        </label>

                        <div class="flex gap-3">

                            <input
                                type="number"
                                step="0.01"
                                id="bearing_temp_m_out"
                                name="bearing_temp_m_out"
                                value="{{ old(
                                    'bearing_temp_m_out',
                                    $parameters['bearing_temp_m_out'] ?? ''
                                ) }}"
                                class="w-full border border-gray-300
                                       rounded-xl px-4 py-3"
                            >

                            <span class="flex items-center
                                         px-4 rounded-xl bg-gray-100
                                         text-gray-600 font-semibold">
                                °C
                            </span>

                        </div>

                    </div>


                    <div>

                        <label
                            for="bearing_temp_m_in"
                            class="block font-semibold text-gray-700 mb-2"
                        >
                            Bearing Temp M-In
                        </label>

                        <div class="flex gap-3">

                            <input
                                type="number"
                                step="0.01"
                                id="bearing_temp_m_in"
                                name="bearing_temp_m_in"
                                value="{{ old(
                                    'bearing_temp_m_in',
                                    $parameters['bearing_temp_m_in'] ?? ''
                                ) }}"
                                class="w-full border border-gray-300
                                       rounded-xl px-4 py-3"
                            >

                            <span class="flex items-center
                                         px-4 rounded-xl bg-gray-100
                                         text-gray-600 font-semibold">
                                °C
                            </span>

                        </div>

                    </div>

                </div>

            </div>


            {{-- PUMP BEARING TEMPERATURE --}}
            <div class="mb-6">

                <h2 class="text-lg font-bold text-[#0F2D5C] mb-4">
                    Pump Bearing Temperature
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div>

                        <label
                            for="bearing_temp_p_in"
                            class="block font-semibold text-gray-700 mb-2"
                        >
                            Bearing Temp P-In
                        </label>

                        <div class="flex gap-3">

                            <input
                                type="number"
                                step="0.01"
                                id="bearing_temp_p_in"
                                name="bearing_temp_p_in"
                                value="{{ old(
                                    'bearing_temp_p_in',
                                    $parameters['bearing_temp_p_in'] ?? ''
                                ) }}"
                                class="w-full border border-gray-300
                                       rounded-xl px-4 py-3"
                            >

                            <span class="flex items-center
                                         px-4 rounded-xl bg-gray-100
                                         text-gray-600 font-semibold">
                                °C
                            </span>

                        </div>

                    </div>


                    <div>

                        <label
                            for="bearing_temp_p_out"
                            class="block font-semibold text-gray-700 mb-2"
                        >
                            Bearing Temp P-Out
                        </label>

                        <div class="flex gap-3">

                            <input
                                type="number"
                                step="0.01"
                                id="bearing_temp_p_out"
                                name="bearing_temp_p_out"
                                value="{{ old(
                                    'bearing_temp_p_out',
                                    $parameters['bearing_temp_p_out'] ?? ''
                                ) }}"
                                class="w-full border border-gray-300
                                       rounded-xl px-4 py-3"
                            >

                            <span class="flex items-center
                                         px-4 rounded-xl bg-gray-100
                                         text-gray-600 font-semibold">
                                °C
                            </span>

                        </div>

                    </div>

                </div>

            </div>


            {{-- FLOW --}}
            <div class="mb-6">

                <label
                    for="flow_rate"
                    class="block font-semibold text-gray-700 mb-2"
                >
                    Flow Rate
                </label>

                <div class="flex gap-3">

                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        id="flow_rate"
                        name="flow_rate"
                        value="{{ old(
                            'flow_rate',
                            $parameters['flow_rate'] ?? ''
                        ) }}"
                        class="w-full border border-gray-300
                               rounded-xl px-4 py-3"
                        placeholder="Enter flow rate"
                    >

                    <span class="flex items-center
                                 px-4 rounded-xl bg-gray-100
                                 text-gray-600 font-semibold">
                        USGPM
                    </span>

                </div>

            </div>


            {{-- LOAD CURRENT --}}
            <div class="mb-6">

                <h2 class="text-lg font-bold text-[#0F2D5C] mb-1">
                    Motor Load Current
                </h2>

                <p class="text-sm text-gray-500 mb-4">
                    Arus motor 3 phase (clamp per fase).
                    Imbalance dihitung otomatis.
                </p>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                    <div>

                        <label
                            for="current_phase_1"
                            class="block font-semibold text-gray-700 mb-2"
                        >
                            Current Phase 1 (L1)
                        </label>

                        <div class="flex gap-3">

                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                id="current_phase_1"
                                name="current_phase_1"
                                value="{{ old(
                                    'current_phase_1',
                                    $parameters['current_phase_1'] ?? ''
                                ) }}"
                                class="w-full border border-gray-300
                                       rounded-xl px-4 py-3"
                            >

                            <span class="flex items-center
                                         px-4 rounded-xl bg-gray-100
                                         text-gray-600 font-semibold">
                                A
                            </span>

                        </div>

                    </div>


                    <div>

                        <label
                            for="current_phase_2"
                            class="block font-semibold text-gray-700 mb-2"
                        >
                            Current Phase 2 (L2)
                        </label>

                        <div class="flex gap-3">

                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                id="current_phase_2"
                                name="current_phase_2"
                                value="{{ old(
                                    'current_phase_2',
                                    $parameters['current_phase_2'] ?? ''
                                ) }}"
                                class="w-full border border-gray-300
                                       rounded-xl px-4 py-3"
                            >

                            <span class="flex items-center
                                         px-4 rounded-xl bg-gray-100
                                         text-gray-600 font-semibold">
                                A
                            </span>

                        </div>

                    </div>


                    <div>

                        <label
                            for="current_phase_3"
                            class="block font-semibold text-gray-700 mb-2"
                        >
                            Current Phase 3 (L3)
                        </label>

                        <div class="flex gap-3">

                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                id="current_phase_3"
                                name="current_phase_3"
                                value="{{ old(
                                    'current_phase_3',
                                    $parameters['current_phase_3'] ?? ''
                                ) }}"
                                class="w-full border border-gray-300
                                       rounded-xl px-4 py-3"
                            >

                            <span class="flex items-center
                                         px-4 rounded-xl bg-gray-100
                                         text-gray-600 font-semibold">
                                A
                            </span>

                        </div>

                    </div>

                </div>

            </div>


            {{-- BENTLEY PROXIMITY (SHAFT DISPLACEMENT) --}}
            <div class="mb-8">

                <h2 class="text-lg font-bold text-[#0F2D5C] mb-1">
                    Bentley Shaft Displacement (Proximity)
                </h2>

                <p class="text-sm text-gray-500 mb-4">
                    Pembacaan proximitors 3300 —
                    <strong>µm peak-to-peak</strong> radial.
                    (1 mil = 25.4 µm)
                </p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div>

                        <label
                            for="bentley_motor_x"
                            class="block font-semibold text-gray-700 mb-2"
                        >
                            Bentley Motor X (Radial)
                        </label>

                        <div class="flex gap-3">

                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                id="bentley_motor_x"
                                name="bentley_motor_x"
                                value="{{ old(
                                    'bentley_motor_x',
                                    $parameters['bentley_motor_x'] ?? ''
                                ) }}"
                                class="w-full border border-gray-300
                                       rounded-xl px-4 py-3"
                            >

                            <span class="flex items-center
                                         px-4 rounded-xl bg-gray-100
                                         text-gray-600 font-semibold">
                                µm p-p
                            </span>

                        </div>

                    </div>


                    <div>

                        <label
                            for="bentley_motor_y"
                            class="block font-semibold text-gray-700 mb-2"
                        >
                            Bentley Motor Y (Radial)
                        </label>

                        <div class="flex gap-3">

                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                id="bentley_motor_y"
                                name="bentley_motor_y"
                                value="{{ old(
                                    'bentley_motor_y',
                                    $parameters['bentley_motor_y'] ?? ''
                                ) }}"
                                class="w-full border border-gray-300
                                       rounded-xl px-4 py-3"
                            >

                            <span class="flex items-center
                                         px-4 rounded-xl bg-gray-100
                                         text-gray-600 font-semibold">
                                µm p-p
                            </span>

                        </div>

                    </div>


                    <div>

                        <label
                            for="bentley_pump_x"
                            class="block font-semibold text-gray-700 mb-2"
                        >
                            Bentley Pump X (Radial)
                        </label>

                        <div class="flex gap-3">

                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                id="bentley_pump_x"
                                name="bentley_pump_x"
                                value="{{ old(
                                    'bentley_pump_x',
                                    $parameters['bentley_pump_x'] ?? ''
                                ) }}"
                                class="w-full border border-gray-300
                                       rounded-xl px-4 py-3"
                            >

                            <span class="flex items-center
                                         px-4 rounded-xl bg-gray-100
                                         text-gray-600 font-semibold">
                                µm p-p
                            </span>

                        </div>

                    </div>


                    <div>

                        <label
                            for="bentley_pump_y"
                            class="block font-semibold text-gray-700 mb-2"
                        >
                            Bentley Pump Y (Radial)
                        </label>

                        <div class="flex gap-3">

                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                id="bentley_pump_y"
                                name="bentley_pump_y"
                                value="{{ old(
                                    'bentley_pump_y',
                                    $parameters['bentley_pump_y'] ?? ''
                                ) }}"
                                class="w-full border border-gray-300
                                       rounded-xl px-4 py-3"
                            >

                            <span class="flex items-center
                                         px-4 rounded-xl bg-gray-100
                                         text-gray-600 font-semibold">
                                µm p-p
                            </span>

                        </div>

                    </div>

                </div>

            </div>


            {{-- BUTTON --}}
            <div class="flex gap-3">

                <a
                    href="{{ route(
                        'equipment-inspection.show',
                        $equipmentInspection->id
                    ) }}"
                    class="bg-gray-500 hover:bg-gray-600
                           text-white px-6 py-3
                           rounded-xl font-semibold"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="bg-[#0F2D5C] hover:bg-blue-900
                           text-white px-6 py-3
                           rounded-xl font-semibold"
                >
                    Save Operating Parameters
                </button>

            </div>

        </form>

    </div>

</div>

@endsection
