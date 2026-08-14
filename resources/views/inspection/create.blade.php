@extends('layouts.app')

@section('title', 'New Inspection Session')

@section('content')

<div class="max-w-5xl mx-auto px-8 py-8">

    {{-- Header --}}
    <div class="flex justify-between items-center mb-8">

        <div>

            <h1 class="text-3xl font-bold text-[#0F2D5C]">
                New Inspection Session
            </h1>

            <p class="text-gray-500">
                Create New Inspection Session
            </p>

        </div>

        <a
            href="{{ route('inspection.index') }}"
            class="bg-gray-500 hover:bg-gray-600 text-white px-5 py-3 rounded-xl">

            ← Back

        </a>

    </div>


    {{-- Form --}}
    <div class="bg-white rounded-2xl shadow p-8">

        <form
            action="{{ route('inspection.store') }}"
            method="POST">

            @csrf


            <div class="grid grid-cols-2 gap-6">


                {{-- Inspection Date --}}
                <div>

                    <label class="font-semibold">
                        Inspection Date
                    </label>

                    <input
                        type="date"
                        name="inspection_date"
                        value="{{ old('inspection_date') }}"
                        class="w-full mt-2 border rounded-xl p-3"
                        required>

                </div>


                {{-- Inspector --}}
                <div>

                    <label class="font-semibold">
                        Inspector
                    </label>

                    <input
                        type="text"
                        name="inspector"
                        value="{{ old('inspector') }}"
                        placeholder="Nama Inspector"
                        class="w-full mt-2 border rounded-xl p-3"
                        required>

                </div>


                {{-- Remarks --}}
                <div class="col-span-2">

                    <label class="font-semibold">
                        Remarks
                    </label>

                    <textarea
                        name="remarks"
                        rows="4"
                        placeholder="Catatan inspection session..."
                        class="w-full mt-2 border rounded-xl p-3">{{ old('remarks') }}</textarea>

                </div>

            </div>


            {{-- Validation Error --}}
            @if($errors->any())

                <div class="mt-6 bg-red-100 border border-red-300 text-red-700 px-5 py-4 rounded-xl">

                    <ul class="list-disc ml-5">

                        @foreach($errors->all() as $error)

                            <li>
                                {{ $error }}
                            </li>

                        @endforeach

                    </ul>

                </div>

            @endif


            {{-- Save --}}
            <div class="mt-8">

                <button
                    type="submit"
                    class="bg-[#0F2D5C] hover:bg-blue-900 text-white px-6 py-3 rounded-xl">

                    Save Inspection Session

                </button>

            </div>

        </form>

    </div>

</div>

@endsection