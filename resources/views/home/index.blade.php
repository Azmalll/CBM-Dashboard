@extends('layouts.app')

@section('title', 'CBM System')

@section('content')

<div class="min-h-[75vh] flex items-center justify-center">

    <div class="w-full max-w-6xl">

        {{-- ================================================= --}}
        {{-- HEADER --}}
        {{-- ================================================= --}}

        <div class="text-center mb-10">

            <h1 class="text-4xl font-bold text-[#0F2D5C]">
                CBM System
            </h1>

            <p class="text-gray-500 mt-2 text-lg">
                Condition Based Maintenance Monitoring System
            </p>

        </div>


        {{-- ================================================= --}}
        {{-- MENU --}}
        {{-- ================================================= --}}

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">


            {{-- Dashboard --}}

            <a
                href="{{ route('dashboard') }}"
                class="bg-white rounded-2xl shadow-sm p-7
                       hover:shadow-lg hover:-translate-y-1
                       transition duration-200"
            >

                <div class="text-3xl mb-4">
                    📊
                </div>

                <h2 class="text-xl font-bold text-[#0F2D5C]">
                    Dashboard
                </h2>

                <p class="text-gray-500 mt-2">
                    View overall CBM equipment condition
                    and monitoring summary.
                </p>

            </a>


            {{-- Equipment --}}

            <a
                href="{{ route('equipment.index') }}"
                class="bg-white rounded-2xl shadow-sm p-7
                       hover:shadow-lg hover:-translate-y-1
                       transition duration-200"
            >

                <div class="text-3xl mb-4">
                    ⚙️
                </div>

                <h2 class="text-xl font-bold text-[#0F2D5C]">
                    Equipment
                </h2>

                <p class="text-gray-500 mt-2">
                    Manage equipment master data
                    and equipment information.
                </p>

            </a>


            {{-- Measurement Point --}}

            <a
                href="{{ route('measurement-point.index') }}"
                class="bg-white rounded-2xl shadow-sm p-7
                       hover:shadow-lg hover:-translate-y-1
                       transition duration-200"
            >

                <div class="text-3xl mb-4">
                    📍
                </div>

                <h2 class="text-xl font-bold text-[#0F2D5C]">
                    Measurement Point
                </h2>

                <p class="text-gray-500 mt-2">
                    Manage vibration measurement points
                    for each equipment.
                </p>

            </a>


            {{-- Inspection Session --}}

            <a
                href="{{ route('inspection.index') }}"
                class="bg-white rounded-2xl shadow-sm p-7
                       hover:shadow-lg hover:-translate-y-1
                       transition duration-200"
            >

                <div class="text-3xl mb-4">
                    📋
                </div>

                <h2 class="text-xl font-bold text-[#0F2D5C]">
                    Inspection Session
                </h2>

                <p class="text-gray-500 mt-2">
                    Create and manage equipment
                    inspection sessions.
                </p>

            </a>


            {{-- Measurement Result --}}

            <a
                href="{{ route('measurement-result.index') }}"
                class="bg-white rounded-2xl shadow-sm p-7
                       hover:shadow-lg hover:-translate-y-1
                       transition duration-200"
            >

                <div class="text-3xl mb-4">
                    📈
                </div>

                <h2 class="text-xl font-bold text-[#0F2D5C]">
                    Measurement Result
                </h2>

                <p class="text-gray-500 mt-2">
                    View and manage vibration
                    measurement results.
                </p>

            </a>


           {{-- ODX IMPORT --}}

<a
    href="{{ route('odx-import.create') }}"
    class="block bg-white rounded-2xl shadow-sm p-6
           hover:shadow-md transition"
>

    <div class="text-3xl mb-4">
        📥
    </div>

    <h2 class="text-xl font-bold text-blue-900">
        ODX Import
    </h2>

    <p class="text-gray-500 mt-2">
        Import vibration measurement data from Omnitrend ODX file.
    </p>

</a>

        </div>


        {{-- ================================================= --}}
        {{-- FOOTER --}}
        {{-- ================================================= --}}

        <div class="text-center mt-10">

            <p class="text-sm text-gray-400">
                Condition Based Maintenance System
            </p>

        </div>

    </div>

</div>

@endsection