<?php

namespace App\Http\Controllers;

use App\Models\Inspection;
use Illuminate\Http\Request;

class InspectionController extends Controller
{
    /**
     * Display inspection sessions.
     */
    public function index()
    {
        $inspections = Inspection::orderBy(
            'inspection_date',
            'desc'
        )->get();

        return view(
            'inspection.index',
            compact('inspections')
        );
    }


    /**
     * Show create inspection form.
     */
    public function create()
    {
        return view('inspection.create');
    }


    /**
     * Store new inspection session.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([

            'inspection_date' => [
                'required',
                'date'
            ],

            'remarks' => [
                'nullable',
                'string'
            ],

        ]);


        $inspection = new Inspection();


        $inspection->inspection_date =
            $validated['inspection_date'];


        /*
        |--------------------------------------------------------------------------
        | Inspector
        |--------------------------------------------------------------------------
        |
        | Inspector sekarang disimpan pada
        | masing-masing Measurement Result.
        |
        | Kolom inspector pada Inspection masih dipertahankan
        | untuk kompatibilitas data lama.
        |
        */

        $inspection->inspector =
            'Unassigned';


        $inspection->remarks =
            $validated['remarks'] ?? null;


        $inspection->save();


        return redirect()
            ->route('inspection.index')
            ->with(
                'success',
                'Inspection Session berhasil dibuat.'
            );
    }


    /**
     * Display inspection session.
     */
    public function show(
        Inspection $inspection
    ) {
        $inspection->load([
            'equipmentInspections.equipment',
            'equipmentInspections.highestPoint',
        ]);

        return view(
            'inspection.show',
            compact('inspection')
        );
    }


    /**
     * Show edit inspection session form.
     */
    public function edit(
        Inspection $inspection
    ) {
        return view(
            'inspection.edit',
            compact('inspection')
        );
    }


    /**
     * Update inspection session.
     */
    public function update(
        Request $request,
        Inspection $inspection
    ) {
        $validated = $request->validate([

            'inspection_date' => [
                'required',
                'date'
            ],

            'remarks' => [
                'nullable',
                'string'
            ],

        ]);


        $inspection->inspection_date =
            $validated['inspection_date'];


        /*
        |--------------------------------------------------------------------------
        | Inspector
        |--------------------------------------------------------------------------
        |
        | Jangan lagi menggunakan Inspector dari Inspection Session.
        |
        | Inspector measurement disimpan pada:
        | measurement_results.inspector
        |
        */

        $inspection->inspector =
            'Unassigned';


        $inspection->remarks =
            $validated['remarks'] ?? null;


        $inspection->save();


        return redirect()
            ->route(
                'inspection.show',
                $inspection->id
            )
            ->with(
                'success',
                'Inspection Session berhasil diperbarui.'
            );
    }


    /**
     * Delete inspection session.
     */
    public function destroy(
        Inspection $inspection
    ) {
        $inspection->delete();


        return redirect()
            ->route('inspection.index')
            ->with(
                'success',
                'Inspection Session berhasil dihapus.'
            );
    }
}