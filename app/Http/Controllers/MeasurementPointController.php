<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Equipment;
use App\Models\MeasurementPoint;

class MeasurementPointController extends Controller
{
    public function index()
    {
        $points = MeasurementPoint::with('equipment')
                    ->orderBy('id')
                    ->get();

        return view('measurement-point.index', compact('points'));
    }

    public function create()
    {
        $equipments = Equipment::orderBy('equipment_name')->get();

        return view('measurement-point.create', compact('equipments'));
    }

    public function store(Request $request)
    {
        MeasurementPoint::create([
            'equipment_id' => $request->equipment_id,
            'point_name'   => $request->point_name,
            'location'     => $request->location,
            'direction'    => $request->direction,
            'active'       => $request->has('active'),
        ]);

        return redirect()
            ->route('measurement-point.index')
            ->with('success', 'Measurement Point berhasil ditambahkan');
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        $point = MeasurementPoint::findOrFail($id);

        $equipments = Equipment::orderBy('equipment_name')->get();

        return view('measurement-point.edit', compact('point', 'equipments'));
    }

    public function update(Request $request, string $id)
    {
        $point = MeasurementPoint::findOrFail($id);

        $point->update([
            'equipment_id' => $request->equipment_id,
            'point_name'   => $request->point_name,
            'location'     => $request->location,
            'direction'    => $request->direction,
            'active'       => $request->has('active'),
        ]);

        return redirect()
            ->route('measurement-point.index')
            ->with('success', 'Measurement Point berhasil diupdate');
    }

    public function destroy(string $id)
    {
        $point = MeasurementPoint::findOrFail($id);

        $point->delete();

        return redirect()
            ->route('measurement-point.index')
            ->with('success', 'Measurement Point berhasil dihapus');
    }
}