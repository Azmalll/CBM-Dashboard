@extends('layouts.app')

@section('title','Add Measurement Point')

@section('content')

<div class="max-w-5xl mx-auto px-8 py-8">

    <div class="flex justify-between items-center mb-8">

        <div>
            <h1 class="text-3xl font-bold text-[#0F2D5C]">
                Add Measurement Point
            </h1>

            <p class="text-gray-500">
                Create New Measurement Point
            </p>
        </div>

        <a href="{{ route('measurement-point.index') }}"
            class="bg-gray-500 text-white px-5 py-3 rounded-xl">

            ← Back

        </a>

    </div>

    <div class="bg-white rounded-2xl shadow p-8">

        <form action="{{ route('measurement-point.store') }}" method="POST">

            @csrf

            <div class="grid grid-cols-2 gap-6">

                <div>

                    <label class="font-semibold">
                        Equipment
                    </label>

                    <select
                        name="equipment_id"
                        class="w-full mt-2 border rounded-xl p-3">

                        @foreach($equipments as $equipment)

                            <option value="{{ $equipment->id }}">
                                {{ $equipment->equipment_name }}
                            </option>

                        @endforeach

                    </select>

                </div>

                <div>

                    <label class="font-semibold">
                        Point Name
                    </label>

                    <input
                        type="text"
                        name="point_name"
                        class="w-full mt-2 border rounded-xl p-3">

                </div>

                <div>

                    <label class="font-semibold">
                        Location
                    </label>

                    <input
                        type="text"
                        name="location"
                        class="w-full mt-2 border rounded-xl p-3">

                </div>

                <div>

                    <label class="font-semibold">
                        Direction
                    </label>

                    <select
                        name="direction"
                        class="w-full mt-2 border rounded-xl p-3">

                        <option>Horizontal</option>
                        <option>Vertical</option>
                        <option>Axial</option>

                    </select>

                </div>

                <div>

                    <label class="flex items-center gap-3">

                        <input
                            type="checkbox"
                            name="active"
                            checked>

                        Active

                    </label>

                </div>

            </div>

            <div class="mt-8">

                <button
                    class="bg-[#0F2D5C] hover:bg-blue-900 text-white px-6 py-3 rounded-xl">

                    Save Measurement Point

                </button>

            </div>

        </form>

    </div>

</div>

@endsection