<?php

namespace App\Http\Controllers;

use App\Models\MeasurementResult;
use App\Models\MeasurementPoint;
use App\Models\EquipmentInspection;
use Illuminate\Http\Request;

class MeasurementResultController extends Controller
{
    /**
     * Display measurement results.
     */
    public function index(Request $request)
    {
        $query = MeasurementResult::with([
            'inspection',
            'equipmentInspection.equipment',
            'measurementPoint.equipment'
        ]);

        /*
        |--------------------------------------------------------------------------
        | FILTER BY DATE
        |--------------------------------------------------------------------------
        */

        if ($request->filled('measurement_date')) {

            $query->whereDate(
                'measurement_datetime',
                $request->measurement_date
            );
        }


        $results = $query
            ->latest('measurement_datetime')
            ->latest('id')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | AVAILABLE MEASUREMENT DATES
        |--------------------------------------------------------------------------
        */

        $measurementDates = MeasurementResult::query()
            ->whereNotNull('measurement_datetime')
            ->selectRaw(
                'DATE(measurement_datetime) as measurement_date'
            )
            ->distinct()
            ->orderByDesc('measurement_date')
            ->pluck('measurement_date');


        return view(
            'measurement-result.index',
            compact(
                'results',
                'measurementDates'
            )
        );
    }


    /**
     * Create measurement result.
     */
    public function create(
        EquipmentInspection $equipmentInspection
    ) {
        $equipmentInspection->load([
            'inspection',
            'equipment',
            'measurementResults'
        ]);


        $measurementPoints = MeasurementPoint::with('equipment')
            ->where(
                'equipment_id',
                $equipmentInspection->equipment_id
            )
            ->where('active', true)
            ->orderBy('point_name')
            ->get();


        return view(
            'measurement-result.create',
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

            'measurement_datetime' => [
                'required',
                'date'
            ],

            'inspector' => [
                'nullable',
                'string',
                'max:255'
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
        | UPDATE OR CREATE
        |--------------------------------------------------------------------------
        |
        | Equipment Inspection
        | + Measurement Point
        | + Exact Measurement Datetime
        |
        | Tanggal sama tetapi jam berbeda = measurement berbeda.
        |
        */

        $result = MeasurementResult::updateOrCreate(

            [
                'equipment_inspection_id' =>
                    $equipmentInspection->id,

                'measurement_point_id' =>
                    $validated['measurement_point_id'],

                'measurement_datetime' =>
                    $validated['measurement_datetime'],
            ],

            [

                'inspection_id' =>
                    $equipmentInspection->inspection_id,

                'measurement_date' =>
                    date(
                        'Y-m-d',
                        strtotime(
                            $validated['measurement_datetime']
                        )
                    ),

                'inspector' =>
                    $validated['inspector'] ?? null,

                'overall_velocity' =>
                    $validated['overall_velocity'],

                'unit' =>
                    $validated['unit'],

                'peak_value' =>
                    $validated['peak_value'] ?? null,

                'crest_factor' =>
                    $validated['crest_factor'] ?? null,
            ]
        );


        $this->updateEquipmentInspectionHighest(
            $equipmentInspection
        );


        return redirect()
            ->route(
                'inspection.show',
                $equipmentInspection->inspection_id
            )
            ->with(
                'success',
                'Measurement Result berhasil disimpan.'
            );
    }


    /**
     * Display measurement result.
     */
    public function show(
        MeasurementResult $measurementResult
    ) {
        $measurementResult->load([
            'inspection',
            'equipmentInspection.equipment',
            'measurementPoint.equipment'
        ]);


        return view(
            'measurement-result.show',
            compact('measurementResult')
        );
    }


    /**
     * Edit measurement result.
     */
    public function edit(
        MeasurementResult $measurementResult
    ) {
        $measurementResult->load([
            'equipmentInspection',
            'measurementPoint'
        ]);


        $measurementPoints = MeasurementPoint::with('equipment')
            ->where(
                'equipment_id',
                $measurementResult
                    ->equipmentInspection
                    ->equipment_id
            )
            ->where('active', true)
            ->orderBy('point_name')
            ->get();


        return view(
            'measurement-result.edit',
            compact(
                'measurementResult',
                'measurementPoints'
            )
        );
    }


    /**
     * Update measurement result.
     */
    public function update(
        Request $request,
        MeasurementResult $measurementResult
    ) {
        $validated = $request->validate([

            'measurement_point_id' => [
                'required',
                'exists:measurement_points,id'
            ],

            'measurement_datetime' => [
                'required',
                'date'
            ],

            'inspector' => [
                'nullable',
                'string',
                'max:255'
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


        $equipmentInspection =
            $measurementResult->equipmentInspection;


        $measurementResult->measurement_point_id =
            $validated['measurement_point_id'];


        $measurementResult->measurement_datetime =
            $validated['measurement_datetime'];


        $measurementResult->measurement_date =
            date(
                'Y-m-d',
                strtotime(
                    $validated['measurement_datetime']
                )
            );


        $measurementResult->inspector =
            $validated['inspector'] ?? null;


        $measurementResult->overall_velocity =
            $validated['overall_velocity'];


        $measurementResult->unit =
            $validated['unit'];


        $measurementResult->peak_value =
            $validated['peak_value'] ?? null;


        $measurementResult->crest_factor =
            $validated['crest_factor'] ?? null;


        $measurementResult->save();


        $this->updateEquipmentInspectionHighest(
            $equipmentInspection
        );


        return redirect()
            ->route(
                'inspection.show',
                $equipmentInspection->inspection_id
            )
            ->with(
                'success',
                'Measurement Result berhasil diperbarui.'
            );
    }


    /**
     * Update inspector for a single measurement result.
     *
     * Digunakan oleh input Inspector langsung
     * pada masing-masing baris Measurement Result.
     */
    public function updateInspector(
        Request $request,
        MeasurementResult $measurementResult
    ) {
        $validated = $request->validate([

            'inspector' => [
                'nullable',
                'string',
                'max:255'
            ],

        ]);


        $measurementResult->inspector =
            $validated['inspector'] ?? null;


        $measurementResult->save();


        return redirect()
            ->back()
            ->with(
                'success',
                'Inspector berhasil diperbarui.'
            );
    }


    /**
     * Bulk assign inspector by measurement date.
     *
     * Jika only_unassigned = 1:
     * hanya measurement yang inspector-nya masih NULL
     * yang akan diubah.
     *
     * Jika only_unassigned = 0:
     * semua measurement pada tanggal tersebut akan diubah.
     */
    public function bulkAssignInspector(
        Request $request
    ) {
        $validated = $request->validate([

            'measurement_date' => [
                'required',
                'date'
            ],

            'inspector' => [
                'required',
                'string',
                'max:255'
            ],

            'only_unassigned' => [
                'nullable',
                'boolean'
            ],

        ]);


        /*
        |--------------------------------------------------------------------------
        | BUILD QUERY
        |--------------------------------------------------------------------------
        */

        $query = MeasurementResult::whereDate(
            'measurement_datetime',
            $validated['measurement_date']
        );


        /*
        |--------------------------------------------------------------------------
        | ONLY UNASSIGNED
        |--------------------------------------------------------------------------
        */

        if (
            !empty(
                $validated['only_unassigned']
            )
        ) {

            $query->whereNull(
                'inspector'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | UPDATE
        |--------------------------------------------------------------------------
        */

        $updatedCount =
            $query->update([

                'inspector' =>
                    $validated['inspector'],

                'updated_at' =>
                    now(),

            ]);


        return redirect()
            ->route(
                'measurement-result.index',
                [
                    'measurement_date' =>
                        $validated['measurement_date']
                ]
            )
            ->with(
                'success',
                $updatedCount .
                ' measurement berhasil di-assign ke inspector "' .
                $validated['inspector'] .
                '".'
            );
    }


    /**
     * Delete measurement result.
     */
    public function destroy(
        MeasurementResult $measurementResult
    ) {
        $equipmentInspection =
            $measurementResult->equipmentInspection;


        $inspectionId =
            $equipmentInspection->inspection_id;


        $measurementResult->delete();


        $this->updateEquipmentInspectionHighest(
            $equipmentInspection
        );


        return redirect()
            ->route(
                'inspection.show',
                $inspectionId
            )
            ->with(
                'success',
                'Measurement Result berhasil dihapus.'
            );
    }


    /**
     * Update highest overall and highest measurement point.
     */
    private function updateEquipmentInspectionHighest(
        EquipmentInspection $equipmentInspection
    ) {
        $highestResult =
            MeasurementResult::where(
                'equipment_inspection_id',
                $equipmentInspection->id
            )
            ->orderByDesc('overall_velocity')
            ->first();


        /*
        |--------------------------------------------------------------------------
        | NO MEASUREMENT
        |--------------------------------------------------------------------------
        */

        if (!$highestResult) {

            $equipmentInspection->highest_overall =
                0;


            $equipmentInspection->highest_point_id =
                null;


            $equipmentInspection->save();

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | HAS MEASUREMENT
        |--------------------------------------------------------------------------
        */

        $equipmentInspection->highest_overall =
            $highestResult->overall_velocity;


        $equipmentInspection->highest_point_id =
            $highestResult->measurement_point_id;


        $equipmentInspection->save();
    }
}