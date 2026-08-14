@extends('layouts.app')

@section('title', 'Inspection Session')

@section('content')

<div class="max-w-7xl mx-auto px-8 py-8">

    {{-- Header --}}
    <div class="flex justify-between items-center mb-8">

        <div>
            <h1 class="text-3xl font-bold text-[#0F2D5C]">
                Inspection Session
            </h1>

            <p class="text-gray-500">
                Inspection Session History
            </p>
        </div>

        @if(auth()->user()?->isAdmin())
            <a
                href="{{ route('inspection.create') }}"
                class="bg-[#0F2D5C] hover:bg-blue-900 text-white px-6 py-3 rounded-xl">

                + New Inspection Session

            </a>
        @endif

    </div>


    {{-- Success Message --}}
    @if(session('success'))

        <div class="mb-6 bg-green-100 border border-green-300 text-green-700 px-5 py-4 rounded-xl">

            {{ session('success') }}

        </div>

    @endif


    {{-- Table --}}
    <div class="bg-white rounded-2xl shadow overflow-hidden">

        <table class="w-full">

            <thead class="bg-slate-100">

                <tr>

                    <th class="px-6 py-4 text-left">
                        No
                    </th>

                    <th class="px-6 py-4 text-left">
                        Inspection Date
                    </th>

                    <th class="px-6 py-4 text-left">
                        Inspector
                    </th>

                    <th class="px-6 py-4 text-left">
                        Remarks
                    </th>

                    <th class="px-6 py-4 text-left">
                        Action
                    </th>

                </tr>

            </thead>


            <tbody>

                @forelse($inspections as $inspection)

                    <tr class="border-t">

                        <td class="px-6 py-4">
                            {{ $loop->iteration }}
                        </td>


                        <td class="px-6 py-4">

                            {{ $inspection->inspection_date }}

                        </td>


                        <td class="px-6 py-4">

                            {{ $inspection->inspector }}

                        </td>


                        <td class="px-6 py-4">

                            {{ $inspection->remarks ?? '-' }}

                        </td>


                        <td class="px-6 py-4">

                            <div class="flex gap-2">

                                <a
                                    href="{{ route('inspection.show', $inspection->id) }}"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">

                                    View

                                </a>

                                @if(auth()->user()?->isAdmin())
                                    <a
                                        href="{{ route('inspection.edit', $inspection->id) }}"
                                        class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg">

                                        Edit

                                    </a>


                                    <form
                                        action="{{ route('inspection.destroy', $inspection->id) }}"
                                        method="POST"
                                        onsubmit="return confirm('Delete this Inspection Session?')">

                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg">

                                            Delete

                                        </button>

                                    </form>
                                @endif

                            </div>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="5"
                            class="px-6 py-10 text-center text-gray-500">

                            Belum ada Inspection Session

                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>

@endsection