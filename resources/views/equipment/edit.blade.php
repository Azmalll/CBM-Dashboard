@extends('layouts.app')

@section('title', 'Edit Equipment')

@section('content')

<div class="max-w-5xl mx-auto px-8 py-8">

    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-[#0F2D5C]">
                Edit Equipment
            </h1>

            <p class="text-gray-500">
                Update Equipment
            </p>
        </div>

        <a href="{{ route('equipment.index') }}"
            class="bg-gray-500 hover:bg-gray-600 text-white px-5 py-3 rounded-xl">

            ← Back

        </a>

    </div>

    <div class="bg-white rounded-2xl shadow p-8">

        <form action="{{ route('equipment.update', $equipment->id) }}" method="POST">

            @csrf
            @method('PUT')

            <div class="grid grid-cols-2 gap-6">

                <div>
                    <label class="font-semibold">Equipment ID</label>

                    <input
                        type="text"
                        name="equipment_id"
                        value="{{ $equipment->equipment_id }}"
                        class="w-full mt-2 border rounded-xl p-3">
                </div>

                <div>
                    <label class="font-semibold">Equipment Name</label>

                    <input
                        type="text"
                        name="equipment_name"
                        value="{{ $equipment->equipment_name }}"
                        class="w-full mt-2 border rounded-xl p-3">
                </div>

                <div>
                    <label class="font-semibold">Area</label>

                    <input
                        type="text"
                        name="area"
                        value="{{ $equipment->area }}"
                        class="w-full mt-2 border rounded-xl p-3">
                </div>

                <div>
                    <label class="font-semibold">Plant</label>

                    <input
                        type="text"
                        name="plant"
                        value="{{ $equipment->plant }}"
                        class="w-full mt-2 border rounded-xl p-3">
                </div>

                <div>
                    <label class="font-semibold">Machine Type</label>

                    <input
                        type="text"
                        name="machine_type"
                        value="{{ $equipment->machine_type }}"
                        class="w-full mt-2 border rounded-xl p-3">
                </div>

                <div>
                    <label class="font-semibold">Priority</label>

                    <select
                        name="priority"
                        class="w-full mt-2 border rounded-xl p-3">

                        <option value="Low" {{ $equipment->priority=='Low'?'selected':'' }}>Low</option>
                        <option value="Medium" {{ $equipment->priority=='Medium'?'selected':'' }}>Medium</option>
                        <option value="High" {{ $equipment->priority=='High'?'selected':'' }}>High</option>
                        <option value="Critical" {{ $equipment->priority=='Critical'?'selected':'' }}>Critical</option>

                    </select>
                </div>

                <div>
                    <label class="font-semibold">Status</label>

                    <select
                        name="status"
                        class="w-full mt-2 border rounded-xl p-3">

                        <option value="Normal" {{ $equipment->status=='Normal'?'selected':'' }}>Normal</option>
                        <option value="Alert" {{ $equipment->status=='Alert'?'selected':'' }}>Alert</option>
                        <option value="Danger" {{ $equipment->status=='Danger'?'selected':'' }}>Danger</option>

                    </select>
                </div>

            </div>

            <div class="mt-8">

                <button
                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-xl">

                    Update Equipment

                </button>

            </div>

        </form>

    </div>

</div>

@endsection