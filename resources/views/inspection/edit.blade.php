@extends('layouts.app')

@section('title', 'Edit Inspection Session')

@section('content')

<div class="max-w-4xl mx-auto py-10 px-6">

    {{-- HEADER --}}
    <div class="flex items-center justify-between mb-8">

        <div>

            <h1 class="text-3xl font-bold text-blue-900">
                Edit Inspection Session
            </h1>

            <p class="text-gray-500 mt-1">
                Update inspection session information
            </p>

        </div>


        <a
            href="{{ route('inspection.show', $inspection->id) }}"
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


    {{-- ERROR MESSAGE --}}
    @if($errors->any())

        <div class="mb-6 bg-red-100 border border-red-300
                    text-red-800 px-5 py-4 rounded-xl">

            <div class="font-semibold mb-2">
                Update gagal:
            </div>

            <ul class="list-disc list-inside">

                @foreach($errors->all() as $error)

                    <li>
                        {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    @endif


    {{-- FORM --}}
    <div class="bg-white rounded-2xl shadow-sm p-8">

        <form
            method="POST"
            action="{{ route('inspection.update', $inspection->id) }}"
        >

            @csrf

            @method('PUT')


            {{-- INSPECTION DATE --}}
            <div class="mb-6">

                <label
                    for="inspection_date"
                    class="block text-sm font-semibold text-gray-700 mb-2"
                >
                    Inspection Date
                </label>

                <input
                    type="date"
                    id="inspection_date"
                    name="inspection_date"
                    value="{{ old('inspection_date', $inspection->inspection_date) }}"
                    required
                    class="block w-full border border-gray-300
                           rounded-xl px-4 py-3
                           text-gray-700
                           focus:ring-2 focus:ring-blue-500
                           focus:border-blue-500"
                >

            </div>


            {{-- INFO --}}
            <div class="bg-blue-50 border border-blue-200
                        rounded-xl p-5 mb-6">

                <h2 class="font-semibold text-blue-800 mb-2">
                    Inspector Information
                </h2>

                <p class="text-sm text-blue-700">

                    Inspector is assigned to each measurement result,
                    not to the inspection session.

                </p>

                <p class="text-sm text-blue-700 mt-2">

                    Use the Measurement Results page to assign
                    inspectors individually or by measurement date.

                </p>

            </div>


            {{-- REMARKS --}}
            <div class="mb-8">

                <label
                    for="remarks"
                    class="block text-sm font-semibold text-gray-700 mb-2"
                >
                    Remarks
                </label>

                <textarea
                    id="remarks"
                    name="remarks"
                    rows="5"
                    class="block w-full border border-gray-300
                           rounded-xl px-4 py-3
                           text-gray-700
                           focus:ring-2 focus:ring-blue-500
                           focus:border-blue-500"
                    placeholder="Enter inspection remarks..."
                >{{ old('remarks', $inspection->remarks) }}</textarea>

            </div>


            {{-- BUTTON --}}
            <div class="flex items-center gap-3">

                <button
                    type="submit"
                    class="bg-blue-900 hover:bg-blue-950
                           text-white
                           font-semibold
                           px-6 py-3
                           rounded-xl
                           transition"
                >
                    Save Changes
                </button>


                <a
                    href="{{ route('inspection.show', $inspection->id) }}"
                    class="bg-gray-200 hover:bg-gray-300
                           text-gray-700
                           font-semibold
                           px-6 py-3
                           rounded-xl
                           transition"
                >
                    Cancel
                </a>

            </div>

        </form>

    </div>

</div>

@endsection