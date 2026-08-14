<?php

namespace App\Http\Controllers;

use App\Models\EquipmentInspection;
use App\Models\MeasurementPoint;
use App\Models\MeasurementResult;
use Illuminate\Http\Request;

class EquipmentInspectionMeasurementController extends Controller
{
    /**
     * Show form to add measurement result.
     */
    public function create(EquipmentInspection $equipmentInspection)
    {
        $equipmentInspection->load([
            'inspection',
            'equipment'
        ]);

        $measurementPoints = MeasurementPoint::where(
            'equipment_id',
            $equipmentInspection->equipment_id
        )
        ->where('active', true)
        ->orderBy('point_name')
        ->get();

        return view(
            'equipment-inspection.measurement.create',
            compact(
                'equipmentInspection',
                'measurementPoints'
            )
        );
    }


    /**
     * Store measurement result.
     */
    public function store(
        Request $request,
        EquipmentInspection $equipmentInspection
    ) {
        $validated = $request->validate([

            'measurement_point_id' => [
                'required',
                'exists:measurement_points,id'
            ],

            'overall_velocity' => [
                'required',
                'numeric',
                'min:0'
            ],

            'unit' => [
                'required',
                'in:mm/s RMS,inch/s RMS'
            ],

            'peak_value' => [
                'nullable',
                'numeric',
                'min:0'
            ],

            'crest_factor' => [
                'nullable',
                'numeric',
                'min:0'
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Simpan Measurement Result
        |--------------------------------------------------------------------------
        */

        $measurement = new MeasurementResult();

        $measurement->inspection_id =
            $equipmentInspection->inspection_id;

        $measurement->equipment_inspection_id =
            $equipmentInspection->id;

        $measurement->measurement_point_id =
            $validated['measurement_point_id'];

        $measurement->overall_velocity =
            $validated['overall_velocity'];

        $measurement->unit =
            $validated['unit'];

        $measurement->peak_value =
            $validated['peak_value'] ?? null;

        $measurement->crest_factor =
            $validated['crest_factor'] ?? null;

        $measurement->save();


        /*
        |--------------------------------------------------------------------------
        | Cari Measurement dengan Overall Tertinggi
        |--------------------------------------------------------------------------
        */

        $highestMeasurement = MeasurementResult::where(
            'equipment_inspection_id',
            $equipmentInspection->id
        )
        ->orderByDesc('overall_velocity')
        ->first();


        /*
        |--------------------------------------------------------------------------
        | Update Equipment Inspection
        |--------------------------------------------------------------------------
        */

        if ($highestMeasurement) {

            $equipmentInspection->highest_overall =
                $highestMeasurement->overall_velocity;

            $equipmentInspection->highest_point_id =
                $highestMeasurement->measurement_point_id;

            /*
             * Severity sementara tetap Pending.
             * Threshold severity akan kita buat setelahnya.
             */
            if (!$equipmentInspection->severity) {
                $equipmentInspection->severity = 'Pending';
            }

            $equipmentInspection->save();
        }


        /*
        |--------------------------------------------------------------------------
        | Redirect ke Inspection Session
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route(
                'inspection.show',
                $equipmentInspection->inspection_id
            )
            ->with(
                'success',
                'Measurement Result berhasil ditambahkan.'
            );
    }
}