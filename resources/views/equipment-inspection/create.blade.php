@extends('layouts.app')

@section('content')

<div class="max-w-4xl mx-auto py-10 px-6">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-8">

        <div>
            <h1 class="text-3xl font-bold text-slate-800">
                Add Equipment Inspection
            </h1>

            <p class="text-slate-500 mt-1">
                Add equipment to this Inspection Session
            </p>
        </div>

        <a
            href="{{ route('inspection.show', $inspection) }}"
            class="px-5 py-3 rounded-xl bg-slate-500 text-white font-semibold hover:bg-slate-600 transition"
        >
            ← Back
        </a>

    </div>


    {{-- Form --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">

        <form
            action="{{ route('inspection.equipment.store', $inspection) }}"
            method="POST"
        >

            @csrf


            {{-- Inspection Session --}}
            <div class="mb-6">

                <label class="block text-sm font-semibold text-slate-700 mb-2">
                    Inspection Session
                </label>

                <div class="w-full rounded-xl border border-slate-300 bg-slate-100 px-4 py-3 text-slate-700">

                    {{ $inspection->inspection_date }}

                    @if($inspection->inspector)
                        — {{ $inspection->inspector }}
                    @endif

                </div>

            </div>


            {{-- Equipment --}}
            <div class="mb-8">

                <label
                    for="equipment_id"
                    class="block text-sm font-semibold text-slate-700 mb-2"
                >
                    Equipment
                </label>

                <select
                    name="equipment_id"
                    id="equipment_id"
                    required
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                >

                    <option value="">
                        -- Select Equipment --
                    </option>

                    @foreach($equipments as $equipment)

                        <option
                            value="{{ $equipment->id }}"
                            {{ old('equipment_id') == $equipment->id ? 'selected' : '' }}
                        >
                            {{ $equipment->equipment_name }}
                        </option>

                    @endforeach

                </select>

                @error('equipment_id')
                    <p class="text-red-500 text-sm mt-2">
                        {{ $message }}
                    </p>
                @enderror

            </div>


            {{-- Button --}}
            <div class="flex items-center gap-3">

                <a
                    href="{{ route('inspection.show', $inspection) }}"
                    class="px-6 py-3 rounded-xl border border-slate-300 text-slate-700 font-semibold hover:bg-slate-50 transition"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="px-6 py-3 rounded-xl bg-blue-900 text-white font-semibold hover:bg-blue-800 transition"
                >
                    Save Equipment
                </button>

            </div>

        </form>

    </div>

</div>

@endsection