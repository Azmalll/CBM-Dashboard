@extends('layouts.app')

@section('title', 'Unit Correction')

@section('content')

<div class="max-w-5xl mx-auto px-6 py-10">

    {{-- HEADER --}}
    <div class="flex items-center justify-between mb-8">

        <div>
            <h1 class="text-3xl font-bold text-[#0F2D5C]">
                Unit Correction
            </h1>

            <p class="text-gray-500 mt-1">
                Koreksi massal unit Operating Parameters
                (mis. data tersimpan °C padahal sebenarnya °F).
                <strong>Preview dulu, baru konfirmasi.</strong>
            </p>
        </div>

        <a
            href="{{ route('dashboard') }}"
            class="bg-gray-500 hover:bg-gray-600
                   text-white px-5 py-3
                   rounded-xl font-semibold"
        >
            ← Back
        </a>

    </div>


    {{-- FLASH --}}
    @if(session('success'))
        <div class="bg-green-50 border border-green-200
                    text-green-700 px-6 py-4
                    rounded-xl mb-6">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200
                    text-red-700 px-6 py-4
                    rounded-xl mb-6">
            {{ session('error') }}
        </div>
    @endif


    {{-- FORM --}}
    <div class="bg-white rounded-2xl shadow-sm p-8">

        <form
            method="POST"
            id="correctionForm"
            action="{{ route('unit-correction.apply') }}"
        >

            @csrf

            {{-- SCOPE --}}
            <h2 class="text-lg font-bold text-[#0F2D5C] mb-4">
                1 · Scope Data
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">

                <div>
                    <label class="block font-semibold text-gray-700 mb-2">
                        Area
                    </label>

                    <select
                        id="correctionArea"
                        name="area"
                        class="w-full border border-gray-300
                               rounded-xl px-4 py-3 bg-white"
                        onchange="onAreaChange()"
                    >
                        <option value="">Semua area</option>

                        @foreach($areas as $area)
                            <option value="{{ $area }}">{{ $area }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-gray-700 mb-2">
                        Equipment
                    </label>

                    <select
                        id="correctionEquipment"
                        name="equipment_id"
                        class="w-full border border-gray-300
                               rounded-xl px-4 py-3 bg-white"
                    >
                        <option value="">Semua equipment</option>

                        @foreach($equipments as $equipment)
                            <option
                                value="{{ $equipment->id }}"
                                data-area="{{ $equipment->area }}"
                            >
                                {{ $equipment->equipment_name }}
                                {{ $equipment->area ? ' · ' . $equipment->area : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

            </div>

            <div class="mb-8">

                <label class="block font-semibold text-gray-700 mb-3">
                    Inspection Date
                </label>

                <div class="flex flex-wrap gap-4 mb-3">

                    <label class="flex items-center gap-2 text-sm">
                        <input
                            type="radio"
                            name="date_mode"
                            value="all"
                            checked
                            class="correction-date-radio"
                        >
                        Semua tanggal
                    </label>

                    <label class="flex items-center gap-2 text-sm">
                        <input
                            type="radio"
                            name="date_mode"
                            value="single"
                            class="correction-date-radio"
                        >
                        Tanggal tunggal
                    </label>

                    <label class="flex items-center gap-2 text-sm">
                        <input
                            type="radio"
                            name="date_mode"
                            value="range"
                            class="correction-date-radio"
                        >
                        Rentang tanggal
                    </label>

                </div>

                <div
                    id="correctionDateSingle"
                    class="hidden"
                >
                    <div class="flex gap-3">
                        <input
                            type="date"
                            id="correctionDateFrom"
                            name="date_from"
                            class="border border-gray-300
                                   rounded-xl px-4 py-3"
                        >
                    </div>
                </div>

                <div id="correctionDateRange" class="hidden">
                    <div class="flex flex-wrap items-center gap-3">
                        <input
                            type="date"
                            id="correctionDateRangeFrom"
                            name="date_from"
                            class="border border-gray-300
                                   rounded-xl px-4 py-3"
                        >
                        <span class="text-gray-400">s/d</span>
                        <input
                            type="date"
                            id="correctionDateRangeTo"
                            name="date_to"
                            class="border border-gray-300
                                   rounded-xl px-4 py-3"
                        >
                    </div>
                </div>

            </div>


            {{-- PARAMETER + UNITS --}}
            <h2 class="text-lg font-bold text-[#0F2D5C] mb-4">
                2 · Parameter & Unit
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-3">

                <div>
                    <label class="block font-semibold text-gray-700 mb-2">
                        Parameter
                    </label>

                    <select
                        id="correctionParameter"
                        name="parameter_key"
                        class="w-full border border-gray-300
                               rounded-xl px-4 py-3 bg-white"
                        onchange="updateUnitOptions()"
                    >
                        @foreach($parameterOptions as $option)
                            <option
                                value="{{ $option['key'] }}"
                                data-units='@json($option['units'])'
                            >
                                {{ $option['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-gray-700 mb-2">
                        Unit di Sistem (tersimpan sebagai)
                    </label>

                    <select
                        id="correctionStoredUnit"
                        name="stored_unit"
                        class="w-full border border-gray-300
                               rounded-xl px-4 py-3 bg-white"
                    >
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-gray-700 mb-2">
                        Unit Sebenarnya (koreksi ke)
                    </label>

                    <select
                        id="correctionActualUnit"
                        name="actual_unit"
                        class="w-full border border-gray-300
                               rounded-xl px-4 py-3 bg-white"
                    >
                    </select>
                </div>

            </div>

            <p class="text-xs text-gray-400 mb-8">
                Arah konversi: nilai yang TERSIMPAN sebagai
                <em>Unit di Sistem</em>, diperbaiki agar
                TERSIMPAN sebagai <em>Unit Sebenarnya</em>.
                Contoh: “°C → °F” = angka yang tersimpan
                (padahal °F) dikonversi menjadi °C yang benar
                dan ditimpa ke database.
            </p>


            {{-- PREVIEW --}}
            <div class="mb-8">

                <button
                    type="button"
                    onclick="runPreview()"
                    id="previewButton"
                    class="bg-white border border-[#0F2D5C]
                           text-[#0F2D5C] hover:bg-blue-50
                           px-6 py-3 rounded-xl font-semibold"
                >
                    🔍 Preview Perubahan
                </button>

                <div id="previewResult" class="mt-5"></div>

            </div>


            {{-- APPLY --}}
            <div class="flex gap-3 items-start">

                <button
                    type="submit"
                    id="applyButton"
                    class="bg-red-600 hover:bg-red-700
                           text-white px-6 py-3
                           rounded-xl font-semibold"
                    onclick="return confirmApply()"
                >
                    ⚠️ Apply Correction
                </button>

                <p class="text-xs text-gray-400 mt-1">
                    Apply hanya aktif setelah preview sukses.
                    Setiap aksi tercatat di audit log (bisa di-undo).
                </p>

            </div>

        </form>

    </div>


    {{-- HISTORY / AUDIT LOG --}}
    <div class="bg-white rounded-2xl shadow-sm p-8 mt-8">

        <h2 class="text-lg font-bold text-[#0F2D5C] mb-4">
            3 · Audit Log
        </h2>

        @if($logs->count())

            <div class="overflow-x-auto border border-gray-200 rounded-xl">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Waktu</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Parameter</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Konversi</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Scope</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">Record</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-600">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($logs as $log)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap text-gray-600">
                                    {{ $log->created_at->format('d M Y H:i') }}
                                </td>
                                <td class="px-4 py-3 text-gray-800">
                                    {{ $log->parameter_key }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                    {{ $log->stored_unit }} → {{ $log->actual_unit }}
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ $log->scope_description }}
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-[#0F2D5C]">
                                    {{ $log->records_affected }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($log->reverted_at)
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                                            Undone
                                        </span>
                                    @else
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                                            Active
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if(!$log->reverted_at)
                                        <form
                                            method="POST"
                                            action="{{ route('unit-correction.undo', $log->id) }}"
                                            onsubmit="return confirm('Undo correction ini? Nilai akan dikembalikan ke sebelum konversi.');"
                                        >
                                            @csrf
                                            <button
                                                type="submit"
                                                class="text-xs font-semibold text-red-600 hover:underline"
                                            >
                                                Undo
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        @else

            <p class="text-gray-400 text-sm">
                Belum ada koreksi unit yang tercatat.
            </p>

        @endif

    </div>

</div>


<script>
    let previewState = null;

    function onAreaChange() {
        // When an area is selected, filter the equipment options visually
        // (server-side the equipment_id filter takes precedence anyway).
        const area = document.getElementById('correctionArea').value;
        const equipmentSelect = document.getElementById('correctionEquipment');

        Array.from(equipmentSelect.options).forEach(function (option) {
            if (option.value === '') {
                option.disabled = false;
                option.hidden = false;
                return;
            }
            const optionArea = option.dataset.area || '';
            const match = !area || optionArea === area;
            option.hidden = !match;
            option.disabled = !match && option.value === equipmentSelect.value;
        });

        if (area && equipmentSelect.value !== '') {
            const selected = equipmentSelect.options[equipmentSelect.selectedIndex];
            if (selected && (selected.dataset.area || '') !== area) {
                equipmentSelect.value = '';
            }
        }
    }

    function currentUnits() {
        const paramSelect = document.getElementById('correctionParameter');
        const option = paramSelect.options[paramSelect.selectedIndex];
        try {
            return JSON.parse(option.dataset.units || '[]');
        } catch (e) {
            return [];
        }
    }

    function fillUnitSelect(selectId, units, placeholder) {
        const select = document.getElementById(selectId);
        select.innerHTML = '';

        units.forEach(function (unit, index) {
            const option = document.createElement('option');
            option.value = unit;
            option.textContent = unit;
            select.appendChild(option);
        });

        // Stored defaults to the first (canonical) unit.
        select.selectedIndex = 0;
    }

    function updateUnitOptions() {
        const units = currentUnits();
        fillUnitSelect('correctionStoredUnit', units);
        fillUnitSelect('correctionActualUnit', units);

        // Default: stored = first unit, actual = second if it exists.
        if (units.length > 1) {
            document.getElementById('correctionActualUnit').selectedIndex = 1;
        }

        previewState = null;
        document.getElementById('previewResult').innerHTML = '';
        document.getElementById('applyButton').disabled = true;
    }

    function readForm() {
        const form = document.getElementById('correctionForm');
        const data = new FormData(form);

        // Only include date fields when their mode is active.
        const mode = (form.querySelector('input[name="date_mode"]:checked') || {}).value || 'all';

        return {
            area: form.elements['area'].value,
            equipment_id: form.elements['equipment_id'].value,
            parameter_key: form.elements['parameter_key'].value,
            stored_unit: form.elements['stored_unit'].value,
            actual_unit: form.elements['actual_unit'].value,
            date_mode: mode,
            date_from: (mode === 'range'
                ? document.getElementById('correctionDateRangeFrom').value
                : document.getElementById('correctionDateFrom').value) || '',
            date_to: (mode === 'range'
                ? document.getElementById('correctionDateRangeTo').value
                : '') || '',
        };
    }

    async function runPreview() {
        const payload = readForm();
        const button = document.getElementById('previewButton');
        const result = document.getElementById('previewResult');

        if (payload.stored_unit === payload.actual_unit) {
            result.innerHTML =
                '<div class="bg-yellow-50 border border-yellow-200 text-yellow-700 rounded-xl p-4 text-sm">' +
                'Unit "tersimpan" dan "sebenarnya" sama — tidak ada konversi.' +
                '</div>';
            previewState = null;
            document.getElementById('applyButton').disabled = true;
            return;
        }

        button.disabled = true;
        button.textContent = '⏳ Menghitung…';

        try {
            const response = await fetch('{{ route('unit-correction.preview') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify(payload),
            });

            const data = await response.json();

            if (!response.ok) {
                const message = (data.message || 'Preview gagal.');
                result.innerHTML =
                    '<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl p-4 text-sm">' + message + '</div>';
                previewState = null;
                document.getElementById('applyButton').disabled = true;
                return;
            }

            previewState = data;

            if (data.affected === 0) {
                result.innerHTML =
                    '<div class="bg-gray-50 border border-gray-200 text-gray-600 rounded-xl p-4 text-sm">' +
                    'Tidak ada record dengan nilai parameter ini di scope yang dipilih.' +
                    '</div>';
                document.getElementById('applyButton').disabled = true;
                return;
            }

            let rows = data.rows.map(function (row) {
                return (
                    '<tr class="border-b border-gray-100">' +
                    '<td class="px-4 py-2 text-gray-700">' + row.equipment_name + '</td>' +
                    '<td class="px-4 py-2 text-gray-700">' + (row.date || '-') + '</td>' +
                    '<td class="px-4 py-2 text-right font-semibold text-[#0F2D5C]">' + row.before + '</td>' +
                    '<td class="px-4 py-2 text-right font-semibold text-green-700">' + row.after + ' ' + data.actual_unit + '</td>' +
                    '</tr>'
                );
            }).join('');

            result.innerHTML =
                '<div class="border border-green-200 rounded-xl overflow-hidden">' +
                '<div class="bg-green-50 px-4 py-3 text-sm text-green-800 font-semibold">' +
                '✅ ' + data.affected + ' record akan diubah · ' + data.scope_description +
                '</div>' +
                '<div class="overflow-x-auto max-h-80">' +
                '<table class="w-full text-sm">' +
                '<thead class="bg-gray-50">' +
                '<tr>' +
                '<th class="px-4 py-2 text-left font-semibold text-gray-600">Equipment</th>' +
                '<th class="px-4 py-2 text-left font-semibold text-gray-600">Tanggal</th>' +
                '<th class="px-4 py-2 text-right font-semibold text-gray-600">Sekarang</th>' +
                '<th class="px-4 py-2 text-right font-semibold text-gray-600">Menjadi</th>' +
                '</tr>' +
                '</thead>' +
                '<tbody>' + rows + '</tbody>' +
                '</table>' +
                '</div>' +
                '</div>';

            document.getElementById('applyButton').disabled = false;

        } catch (error) {
            result.innerHTML =
                '<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl p-4 text-sm">' +
                'Error: ' + error.message +
                '</div>';
            previewState = null;
            document.getElementById('applyButton').disabled = true;
        } finally {
            button.disabled = false;
            button.textContent = '🔍 Preview Perubahan';
        }
    }

    function confirmApply() {
        if (!previewState || previewState.affected === 0) {
            alert('Jalankan Preview terlebih dahulu sebelum Apply.');
            return false;
        }
        return confirm(
            'KONFIRMASI: ' + previewState.affected +
            ' record akan diubah permanen.\n\n' +
            previewState.scope_description + '\n' +
            'Parameter: ' + previewState.parameter_label + '\n' +
            'Konversi: ' + previewState.stored_unit + ' → ' + previewState.actual_unit +
            '\n\nAksi ini tercatat di audit log dan bisa di-undo. Lanjutkan?'
        );
    }

    // Show/hide date inputs based on selected mode.
    // Disabled inputs are NOT submitted with the form — this prevents
    // the single/range date_from fields from colliding.
    function syncDateInputs() {
        const mode = (document.querySelector('input[name="date_mode"]:checked') || {}).value || 'all';
        const singleBox = document.getElementById('correctionDateSingle');
        const rangeBox = document.getElementById('correctionDateRange');
        const singleInput = document.getElementById('correctionDateFrom');
        const rangeFrom = document.getElementById('correctionDateRangeFrom');
        const rangeTo = document.getElementById('correctionDateRangeTo');

        singleBox.classList.toggle('hidden', mode !== 'single');
        rangeBox.classList.toggle('hidden', mode !== 'range');

        singleInput.disabled = mode !== 'single';
        rangeFrom.disabled = mode !== 'range';
        rangeTo.disabled = mode !== 'range';
    }

    document.querySelectorAll('.correction-date-radio').forEach(function (radio) {
        radio.addEventListener('change', syncDateInputs);
    });

    // Init unit selects + date input state on load.
    document.addEventListener('DOMContentLoaded', function () {
        updateUnitOptions();
        syncDateInputs();
    });
</script>

@endsection
