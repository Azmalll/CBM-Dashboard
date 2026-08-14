@extends('layouts.app')

@section('title','Edit Measurement Point')

@section('content')

<div class="max-w-5xl mx-auto px-8 py-8">

    <div class="flex justify-between items-center mb-8">

        <div>
            <h1 class="text-3xl font-bold text-[#0F2D5C]">
                Edit Measurement Point
            </h1>

            <p class="text-gray-500">
                Update Measurement Point
            </p>
        </div>

        <a href="{{ route('measurement-point.index') }}"
            class="bg-gray-500 text-white px-5 py-3 rounded-xl hover:bg-gray-600">

            ← Back

        </a>

    </div>

    <div class="bg-white rounded-2xl shadow p-8">

        <form action="{{ route('measurement-point.update', $point->id) }}" method="POST">

            @csrf
            @method('PUT')

            <div class="grid grid-cols-2 gap-6">

                {{-- Equipment --}}
                <div>

                    <label class="font-semibold">
                        Equipment
                    </label>

                    <select
                        name="equipment_id"
                        class="w-full mt-2 border rounded-xl p-3">

                        @foreach($equipments as $equipment)

                            <option
                                value="{{ $equipment->id }}"
                                {{ $equipment->id == $point->equipment_id ? 'selected' : '' }}>

                                {{ $equipment->equipment_name }}

                            </option>

                        @endforeach

                    </select>

                </div>

                {{-- Point Name --}}
                <div>

                    <label class="font-semibold">
                        Point Name
                    </label>

                    <input
                        type="text"
                        name="point_name"
                        value="{{ $point->point_name }}"
                        class="w-full mt-2 border rounded-xl p-3">

                </div>

                {{-- Location --}}
                <div>

                    <label class="font-semibold">
                        Location
                    </label>

                    <input
                        type="text"
                        name="location"
                        value="{{ $point->location }}"
                        class="w-full mt-2 border rounded-xl p-3">

                </div>

                {{-- Direction --}}
                <div>

                    <label class="font-semibold">
                        Direction
                    </label>

                    <select
                        name="direction"
                        class="w-full mt-2 border rounded-xl p-3">

                        <option value="Horizontal"
                            {{ $point->direction == 'Horizontal' ? 'selected' : '' }}>
                            Horizontal
                        </option>

                        <option value="Vertical"
                            {{ $point->direction == 'Vertical' ? 'selected' : '' }}>
                            Vertical
                        </option>

                        <option value="Axial"
                            {{ $point->direction == 'Axial' ? 'selected' : '' }}>
                            Axial
                        </option>

                    </select>

                </div>

                {{-- Active --}}
                <div>

                    <label class="flex items-center gap-3 mt-3">

                        <input
                            type="checkbox"
                            name="active"
                            {{ $point->active ? 'checked' : '' }}>

                        Active

                    </label>

                </div>

            </div>

            <div class="mt-8">

                <button
                    type="submit"
                    class="bg-[#0F2D5C] hover:bg-blue-900 text-white px-6 py-3 rounded-xl">

                    Update Measurement Point

                </button>

            </div>

        </form>

    </div>

</div>

@endsection