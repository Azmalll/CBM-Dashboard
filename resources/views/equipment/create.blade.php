@extends('layouts.app')

@section('title', 'Add Equipment')

@section('content')

<div class="max-w-5xl mx-auto px-8 py-8">

    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-[#0F2D5C]">
                Add Equipment
            </h1>

            <p class="text-gray-500">
                Create New Equipment
            </p>
        </div>

        <a href="{{ route('equipment.index') }}"
            class="bg-gray-500 hover:bg-gray-600 text-white px-5 py-3 rounded-xl">

            ← Back

        </a>

    </div>

    <div class="bg-white rounded-2xl shadow p-8">

        <form action="{{ route('equipment.store') }}" method="POST">

            @csrf

            <div class="grid grid-cols-2 gap-6">

                <div>
                    <label class="font-semibold">Equipment ID</label>

                    <input
                        type="text"
                        name="equipment_id"
                        class="w-full mt-2 border rounded-xl p-3">
                </div>

                <div>
                    <label class="font-semibold">Equipment Name</label>

                    <input
                        type="text"
                        name="equipment_name"
                        class="w-full mt-2 border rounded-xl p-3">
                </div>

                <div>
                    <label class="font-semibold">Area</label>

                    <input
                        type="text"
                        name="area"
                        class="w-full mt-2 border rounded-xl p-3">
                </div>

                <div>
                    <label class="font-semibold">Plant</label>

                    <input
                        type="text"
                        name="plant"
                        class="w-full mt-2 border rounded-xl p-3">
                </div>

                <div>
                    <label class="font-semibold">Machine Type</label>

                    <input
                        type="text"
                        name="machine_type"
                        class="w-full mt-2 border rounded-xl p-3">
                </div>

                <div>
                    <label class="font-semibold">Priority</label>

                    <select
                        name="priority"
                        class="w-full mt-2 border rounded-xl p-3">

                        <option>Low</option>
                        <option>Medium</option>
                        <option>High</option>
                        <option>Critical</option>

                    </select>
                </div>

                <div>
                    <label class="font-semibold">Status</label>

                    <select
                        name="status"
                        class="w-full mt-2 border rounded-xl p-3">

                        <option>Normal</option>
                        <option>Alert</option>
                        <option>Danger</option>

                    </select>
                </div>

            </div>

            <div class="mt-8">

                <button
                    class="bg-[#0F2D5C] hover:bg-blue-900 text-white px-6 py-3 rounded-xl">

                    Save Equipment

                </button>

            </div>

        </form>

    </div>

</div>

@endsection