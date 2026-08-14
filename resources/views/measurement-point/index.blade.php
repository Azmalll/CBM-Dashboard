@extends('layouts.app')

@section('title', 'Measurement Point')

@section('content')

<div class="max-w-7xl mx-auto px-8 py-8">

    <div class="flex justify-between items-center mb-8">

        <div>
            <h1 class="text-3xl font-bold text-[#0F2D5C]">
                Measurement Point
            </h1>

            <p class="text-gray-500 mt-1">
                Measurement Point Database
            </p>
        </div>

        {{-- ADD POINT - ADMIN ONLY --}}
        @if(auth()->user()->role === 'admin')
            <a href="{{ route('measurement-point.create') }}"
                class="bg-[#0F2D5C] text-white px-5 py-3 rounded-xl hover:bg-blue-900 transition">

                + Add Point

            </a>
        @endif

    </div>

    @if(session('success'))
        <div
            x-data="{show:true}"
            x-init="setTimeout(()=>show=false,3000)"
            x-show="show"
            class="mb-5 rounded-xl bg-green-100 border border-green-300 px-5 py-4 text-green-700">

            ✅ {{ session('success') }}

        </div>
    @endif

    <div class="bg-white rounded-2xl shadow overflow-hidden">

        <table class="w-full">

            <thead class="bg-gray-100">

                <tr>

                    <th class="p-4 text-left">No</th>

                    <th class="p-4 text-left">Equipment</th>

                    <th class="p-4 text-left">Point</th>

                    <th class="p-4 text-left">Location</th>

                    <th class="p-4 text-left">Direction</th>

                    <th class="p-4 text-left">Status</th>

                    <th class="p-4 text-center">Action</th>

                </tr>

            </thead>

            <tbody>

            @foreach($points as $point)

                <tr class="border-t">

                    <td class="p-4">
                        {{ $loop->iteration }}
                    </td>

                    <td class="p-4">
                        {{ $point->equipment->equipment_name }}
                    </td>

                    <td class="p-4">
                        {{ $point->point_name }}
                    </td>

                    <td class="p-4">
                        {{ $point->location }}
                    </td>

                    <td class="p-4">
                        {{ $point->direction }}
                    </td>

                    <td class="p-4">

                        @if($point->active)

                            <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full">
                                Active
                            </span>

                        @else

                            <span class="bg-red-100 text-red-700 px-3 py-1 rounded-full">
                                Inactive
                            </span>

                        @endif

                    </td>

                    <td class="p-4 text-center">

                        {{-- EDIT & DELETE - ADMIN ONLY --}}
                        @if(auth()->user()->role === 'admin')

                            <a href="{{ route('measurement-point.edit', $point->id) }}"
                                class="bg-yellow-400 px-4 py-2 rounded-lg text-white hover:bg-yellow-500">

                                Edit

                            </a>

                            <a href="#"
                                class="bg-red-500 px-4 py-2 rounded-lg text-white">

                                Delete

                            </a>

                        @else

                            <span class="text-gray-400 text-sm">
                                View Only
                            </span>

                        @endif

                    </td>

                </tr>

            @endforeach

            </tbody>

        </table>

    </div>

</div>

@endsection