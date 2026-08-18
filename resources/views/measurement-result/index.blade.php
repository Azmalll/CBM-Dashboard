@extends('layouts.app')

@section('title', 'Measurement Results')

@section('content')

<div class="space-y-8">

    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-[#0F2D5C]">
                Measurement Results
            </h1>
            <p class="text-gray-500 mt-1">
                Vibration measurement data
            </p>
        </div>
    </div>

    {{-- SUCCESS MESSAGE --}}
    @if(session('success'))
        <div class="bg-green-100 border border-green-300 text-green-800 px-5 py-4 rounded-xl">
            {{ session('success') }}
        </div>
    @endif

    {{-- ERROR MESSAGE --}}
    @if($errors->any())
        <div class="bg-red-100 border border-red-300 text-red-800 px-5 py-4 rounded-xl">
            <div class="font-semibold mb-2">Terjadi kesalahan:</div>
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ========================================================= --}}
    {{-- BULK ASSIGN INSPECTOR - ADMIN ONLY --}}
    {{-- ========================================================= --}}
    @if(auth()->user()?->isAdmin())
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <div class="mb-5">
                <h2 class="text-xl font-bold text-[#0F2D5C]">
                    Bulk Assign Inspector
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    Assign inspector berdasarkan tanggal measurement.
                </p>
            </div>

            <form
                method="POST"
                action="{{ route('measurement-result.bulk-assign-inspector') }}"
            >
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    {{-- DATE --}}
                    <div>
                        <label
                            for="measurement_date"
                            class="block text-sm font-semibold text-gray-700 mb-2"
                        >
                            Measurement Date
                        </label>

                        <select
                            id="measurement_date"
                            name="measurement_date"
                            required
                            class="w-full border border-gray-300 rounded-xl px-4 py-3 bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="">
                                -- Select Date --
                            </option>

                            @foreach($measurementDates as $date)
                                <option
                                    value="{{ $date }}"
                                    {{ request('measurement_date') == $date ? 'selected' : '' }}
                                >
                                    {{ \Carbon\Carbon::parse($date)->format('d M Y') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- INSPECTOR --}}
                    <div>
                        <label
                            for="bulk_inspector"
                            class="block text-sm font-semibold text-gray-700 mb-2"
                        >
                            Inspector
                        </label>

                        <input
                            type="text"
                            id="bulk_inspector"
                            name="inspector"
                            required
                            maxlength="255"
                            placeholder="Contoh: Azmal"
                            class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                    </div>

                    {{-- BUTTON --}}
                    <div class="flex items-end">
                        <button
                            type="submit"
                            onclick="return confirm('Apply inspector ke measurement pada tanggal yang dipilih?')"
                            class="w-full bg-[#0F2D5C] hover:bg-[#0b244b] text-white font-semibold px-5 py-3 rounded-xl transition"
                        >
                            Apply Inspector
                        </button>
                    </div>
                </div>

                {{-- ONLY UNASSIGNED --}}
                <div class="mt-5">
                    <label class="inline-flex items-center gap-3 cursor-pointer">
                        <input
                            type="checkbox"
                            name="only_unassigned"
                            value="1"
                            checked
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                        >

                        <span class="text-sm text-gray-700">
                            <span class="font-semibold">
                                Only assign Unassigned measurements
                            </span>
                            <span class="block text-xs text-gray-500 mt-1">
                                Measurement yang sudah memiliki inspector tidak akan diubah.
                            </span>
                        </span>
                    </label>
                </div>
            </form>
        </div>
    @endif

    {{-- ========================================================= --}}
    {{-- FILTER --}}
    {{-- ========================================================= --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <form
            method="GET"
            action="{{ route('measurement-result.index') }}"
            class="flex flex-col md:flex-row md:items-end gap-4"
        >
            <div class="flex-1">
                <label
                    for="filter_date"
                    class="block text-sm font-semibold text-gray-700 mb-2"
                >
                    Filter Measurement Date
                </label>

                <input
                    type="date"
                    id="filter_date"
                    name="measurement_date"
                    value="{{ request('measurement_date') }}"
                    class="w-full border border-gray-300 rounded-xl px-4 py-3"
                >
            </div>

            <button
                type="submit"
                class="bg-gray-700 hover:bg-gray-800 text-white font-semibold px-6 py-3 rounded-xl transition"
            >
                Filter
            </button>

            <a
                href="{{ route('measurement-result.index') }}"
                class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold px-6 py-3 rounded-xl text-center transition"
            >
                Reset
            </a>
        </form>
    </div>

    {{-- ========================================================= --}}
    {{-- MEASUREMENT TABLE --}}
    {{-- ========================================================= --}}
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="p-6 border-b border-gray-100">
            <h2 class="text-xl font-bold text-[#0F2D5C]">
                Measurement Data
            </h2>
            <p class="text-sm text-gray-500 mt-1">
                Total {{ $results->count() }} measurement
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-4 text-left font-semibold text-gray-600 whitespace-nowrap">Date</th>
                        <th class="px-5 py-4 text-left font-semibold text-gray-600 whitespace-nowrap">Time</th>
                        <th class="px-5 py-4 text-left font-semibold text-gray-600 whitespace-nowrap">Equipment</th>
                        <th class="px-5 py-4 text-left font-semibold text-gray-600 whitespace-nowrap">Measurement Point</th>
                        <th class="px-5 py-4 text-left font-semibold text-gray-600 whitespace-nowrap">Overall</th>
                        <th class="px-5 py-4 text-left font-semibold text-gray-600 whitespace-nowrap">Inspector</th>
                        <th class="px-5 py-4 text-left font-semibold text-gray-600 whitespace-nowrap">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @forelse($results as $result)
                        <tr class="hover:bg-gray-50">

                            {{-- DATE --}}
                            <td class="px-5 py-4 whitespace-nowrap">
                                @if($result->measurement_datetime)
                                    {{ \Carbon\Carbon::parse($result->measurement_datetime)->format('d M Y') }}
                                @else
                                    -
                                @endif
                            </td>

                            {{-- TIME --}}
                            <td class="px-5 py-4 whitespace-nowrap">
                                @if($result->measurement_datetime)
                                    {{ \Carbon\Carbon::parse($result->measurement_datetime)->format('H:i') }}
                                @else
                                    -
                                @endif
                            </td>

                            {{-- EQUIPMENT --}}
                            <td class="px-5 py-4 whitespace-nowrap">
                                {{ optional(optional($result->equipmentInspection)->equipment)->equipment_id ?? '-' }}
                            </td>

                            {{-- MEASUREMENT POINT --}}
                            <td class="px-5 py-4 whitespace-nowrap">
                                {{ optional($result->measurementPoint)->point_name ?? '-' }}
                            </td>

                            {{-- OVERALL --}}
                            <td class="px-5 py-4 whitespace-nowrap">
                                <span class="font-semibold text-[#0F2D5C]">
                                    {{ $result->overall_velocity ?? '-' }}
                                </span>

                                @if($result->unit)
                                    <span class="text-xs text-gray-500">
                                        {{ $result->unit }}
                                    </span>
                                @endif
                            </td>

                            {{-- INSPECTOR --}}
                            <td class="px-5 py-4 whitespace-nowrap">
                                @if($result->inspector)
                                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                                        {{ $result->inspector }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-500">
                                        Unassigned
                                    </span>
                                @endif
                            </td>

                            {{-- ACTION --}}
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">

                                    {{-- VIEW - ADMIN & VIEWER --}}
                                    <a
                                        href="{{ route('measurement-result.show', $result->id) }}"
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-xs font-semibold"
                                    >
                                        View
                                    </a>

                                    {{-- EDIT / DELETE - ADMIN ONLY --}}
                                    @if(auth()->user()?->isAdmin())
                                        <a
                                            href="{{ route('measurement-result.edit', $result->id) }}"
                                            class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-2 rounded-lg text-xs font-semibold"
                                        >
                                            Edit
                                        </a>

                                        <form
                                            method="POST"
                                            action="{{ route('measurement-result.destroy', $result->id) }}"
                                            onsubmit="return confirm('Yakin ingin menghapus measurement ini?')"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-lg text-xs font-semibold"
                                            >
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
                                colspan="7"
                                class="px-5 py-12 text-center text-gray-500"
                            >
                                <div class="text-4xl mb-3">📊</div>
                                <p class="font-semibold text-gray-700">
                                    Belum ada measurement result.
                                </p>
                                <p class="text-sm mt-1">
                                    Import ODX atau tambahkan measurement secara manual.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

@endsection
