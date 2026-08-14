@extends('layouts.app')

@section('title', 'Master Equipment')

@section('content')
<div class="max-w-7xl mx-auto px-8 py-8">

    <div class="flex justify-between items-center mb-8">
        <div>
    <h1 class="text-3xl font-bold text-[#0F2D5C]">
        Master Equipment
    </h1>

    <p class="text-gray-500 mt-1">
        Equipment Database
    </p>
</div>

        <a href="{{ route('equipment.create') }}"
    class="bg-[#0F2D5C] text-white px-5 py-3 rounded-xl hover:bg-blue-900 transition">

    + Add Equipment

</a>

</div>

@if(session('success'))

<div
    x-data="{ show: true }"
    x-init="setTimeout(() => show = false, 3000)"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-300"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"

    class="fixed top-6 right-6 z-50 bg-green-500 text-white px-6 py-4 rounded-xl shadow-2xl">

    ✅ {{ session('success') }}

</div>

@endif
    <div class="mt-8 mb-6">

    <input
        type="text"
        placeholder="Search equipment..."
        class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:outline-none">

</div>

    <div class="bg-white rounded-2xl shadow overflow-hidden">
        <div class="overflow-x-auto">

            <table class="min-w-full text-sm">

                <thead class="bg-slate-100 text-[#0F2D5C]">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">No</th>
                        <th class="px-4 py-3 text-left font-semibold">Equipment ID</th>
                        <th class="px-4 py-3 text-left font-semibold">Equipment Name</th>
                        <th class="px-4 py-3 text-left font-semibold">Area</th>
                        <th class="px-4 py-3 text-left font-semibold">Plant</th>
                        <th class="px-4 py-3 text-left font-semibold">Machine Type</th>
                        <th class="px-4 py-3 text-left font-semibold">Priority</th>
                        <th class="px-4 py-3 text-left font-semibold">Status</th>
                        <th class="px-4 py-3 text-left font-semibold" width="150">Action</th>
                    </tr>
                </thead>

                <tbody>

                @forelse($equipments as $equipment)

                    <tr class="border-t hover:bg-slate-50">

                        <td class="px-4 py-3">{{ $loop->iteration }}</td>

                        <td class="px-4 py-3">{{ $equipment->equipment_id }}</td>

                        <td class="px-4 py-3">{{ $equipment->equipment_name }}</td>

                        <td class="px-4 py-3">{{ $equipment->area }}</td>

                        <td class="px-4 py-3">{{ $equipment->plant }}</td>

                        <td class="px-4 py-3">{{ $equipment->machine_type }}</td>

                        <td class="px-4 py-3">{{ $equipment->priority }}</td>

                        <td class="px-4 py-3">

@if($equipment->status == 'Normal')
    <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-semibold">
        Normal
    </span>

@elseif($equipment->status == 'Alert')
    <span class="px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 text-xs font-semibold">
        Alert
    </span>

@else
    <span class="px-3 py-1 rounded-full bg-red-100 text-red-700 text-xs font-semibold">
        Danger
    </span>
@endif

</td>

                        <td>

                            <div class="flex gap-2">

    <a href="{{ route('equipment.edit', $equipment->id) }}"
   class="px-3 py-1 rounded-lg bg-yellow-400 text-white hover:bg-yellow-500">
    Edit
</a>

    <form action="{{ route('equipment.destroy', $equipment->id) }}"
      method="POST"
      onsubmit="return confirm('Delete this equipment?')">

    @csrf
    @method('DELETE')

    <button
        class="px-3 py-1 rounded-lg bg-red-500 hover:bg-red-600 text-white">

        Delete

    </button>

</form>
</div>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="9" class="text-center">
                            No equipment data
                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>
    </div>

</div>
@endsection