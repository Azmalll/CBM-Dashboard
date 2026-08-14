@extends('layouts.app')

@section('title', 'Inspection Session')

@section('content')

<div class="max-w-6xl mx-auto py-10 px-6">

    {{-- HEADER --}}
    <div class="flex items-center justify-between mb-8">

        <div>

            <h1 class="text-3xl font-bold text-blue-900">
                Inspection Session
            </h1>

            <p class="text-gray-500 mt-1">
                Inspection session details and equipment monitoring
            </p>

        </div>

        <a
            href="{{ route('inspection.index') }}"
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


    {{-- INSPECTION INFORMATION --}}
    <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">

        <h2 class="text-xl font-bold text-blue-900 mb-6">
            Inspection Information
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            {{-- DATE --}}
            <div>

                <p class="text-sm text-gray-500">
                    Inspection Date
                </p>

                <p class="text-lg font-semibold text-gray-800 mt-1">

                    {{ $inspection->inspection_date ?? '-' }}

                </p>

            </div>


            {{-- REMARKS --}}
            <div>

                <p class="text-sm text-gray-500">
                    Remarks
                </p>

                <p class="text-lg font-semibold text-gray-800 mt-1">

                    {{ $inspection->remarks ?? '-' }}

                </p>

            </div>

        </div>

    </div>


    {{-- EQUIPMENT INSPECTION --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">

        <div class="flex items-center justify-between mb-6">

            <div>

                <h2 class="text-xl font-bold text-blue-900">
                    Equipment Inspection
                </h2>

                <p class="text-gray-500 mt-1">
                    Equipment included in this inspection session
                </p>

            </div>


            <a
                href="{{ route('equipment.index') }}"
                class="bg-blue-900 hover:bg-blue-950
                       text-white
                       px-5 py-3
                       rounded-xl
                       font-medium
                       transition"
            >
                Equipment Master
            </a>

        </div>


        {{-- EQUIPMENT LIST --}}
        @if(
            isset($inspection->equipmentInspections) &&
            $inspection->equipmentInspections->count()
        )

            <div class="overflow-x-auto">

                <table class="w-full">

                    <thead>

                        <tr class="border-b">

                            <th class="text-left py-4 px-3">
                                No
                            </th>

                            <th class="text-left py-4 px-3">
                                Equipment
                            </th>

                            <th class="text-left py-4 px-3">
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @foreach(
                            $inspection->equipmentInspections
                            as $equipmentInspection
                        )

                            <tr class="border-b hover:bg-gray-50">

                                <td class="py-4 px-3">

                                    {{ $loop->iteration }}

                                </td>


                                <td class="py-4 px-3">

                                    <div>

                                        <p class="font-semibold text-gray-800">

                                            {{ $equipmentInspection->equipment->equipment_name ?? '-' }}

                                        </p>

                                    </div>

                                </td>


                                <td class="py-4 px-3">

                                    <div class="flex gap-2">

                                        {{-- VIEW --}}
                                        <a
                                            href="{{ route(
                                                'equipment-inspection.show',
                                                $equipmentInspection->id
                                            ) }}"
                                            class="bg-blue-600 hover:bg-blue-700
                                                   text-white
                                                   px-4 py-2
                                                   rounded-lg
                                                   text-sm"
                                        >
                                            View
                                        </a>


                                        {{-- TREND --}}
                                        <a
                                            href="{{ route(
                                                'equipment-inspection.trend',
                                                $equipmentInspection->id
                                            ) }}"
                                            class="bg-purple-600 hover:bg-purple-700
                                                   text-white
                                                   px-4 py-2
                                                   rounded-lg
                                                   text-sm"
                                        >
                                            Trend
                                        </a>

                                    </div>

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>


        @else

            <div class="bg-gray-50 rounded-xl p-8 text-center">

                <p class="text-gray-500">

                    No equipment has been added
                    to this inspection session yet.

                </p>

            </div>

        @endif

    </div>

</div>

@endsection