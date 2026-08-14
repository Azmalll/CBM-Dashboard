<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Equipment;

class EquipmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
{
    $equipments = Equipment::orderBy('equipment_id')->get();

    return view('equipment.index', compact('equipments'));
}

    /**
     * Show the form for creating a new resource.
     */
    public function create()
{
    return view('equipment.create');
}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
{
    Equipment::create([
        'equipment_id'   => $request->equipment_id,
        'equipment_name' => $request->equipment_name,
        'area'           => $request->area,
        'plant'          => $request->plant,
        'machine_type'   => $request->machine_type,
        'priority'       => $request->priority,
        'status'         => $request->status,
    ]);

    return redirect()->route('equipment.index')
                     ->with('success', 'Equipment berhasil ditambahkan');
}

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
{
    $equipment = Equipment::findOrFail($id);

    return view('equipment.edit', compact('equipment'));
}

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
{
    $equipment = Equipment::findOrFail($id);

    $equipment->update([
        'equipment_id'   => $request->equipment_id,
        'equipment_name' => $request->equipment_name,
        'area'           => $request->area,
        'plant'          => $request->plant,
        'machine_type'   => $request->machine_type,
        'priority'       => $request->priority,
        'status'         => $request->status,
    ]);

    return redirect()->route('equipment.index')
        ->with('success', 'Equipment berhasil diupdate');
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
{
    $equipment = Equipment::findOrFail($id);

    $equipment->delete();

    return redirect()
        ->route('equipment.index')
        ->with('success', 'Equipment berhasil dihapus');
}
}
