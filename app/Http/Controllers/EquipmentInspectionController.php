<?php

namespace App\Http\Controllers;

use App\Models\EquipmentInspection;
use App\Models\MeasurementPoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EquipmentInspectionController extends Controller
{
    /**
     * Display the specified equipment inspection.
     */
    public function show(EquipmentInspection $equipmentInspection)
    {
        $equipmentInspection->load([
            'inspection',
            'equipment',
            'highestPoint',
            'measurementResults.measurementPoint',
        ]);

        return view(
            'equipment-inspection.show',
            compact('equipmentInspection')
        );
    }


    /**
     * Show the form for editing the equipment inspection analysis.
     */
    public function edit(EquipmentInspection $equipmentInspection)
    {
        $equipmentInspection->load([
            'inspection',
            'equipment',
            'highestPoint',
            'measurementResults.measurementPoint',
        ]);

        $measurementPoints = MeasurementPoint::with('equipment')
            ->where('active', true)
            ->orderBy('point_name')
            ->get();

        return view(
            'equipment-inspection.edit',
            compact(
                'equipmentInspection',
                'measurementPoints'
            )
        );
    }


    /**
     * Update the equipment inspection analysis.
     */
    public function update(
        Request $request,
        EquipmentInspection $equipmentInspection
    ) {
        $validated = $request->validate([
            'highest_overall' => [
                'required',
                'numeric',
                'min:0',
            ],

            'highest_point_id' => [
                'nullable',
                'exists:measurement_points,id'
            ],

            'severity' => [
                'required',
                'string',
                'in:Normal,Alert,Danger,Critical'
            ],

            'diagnosis' => [
                'nullable',
                'string',
            ],

            'recommendation' => [
                'nullable',
                'string',
            ],

            'report_file' => [
                'nullable',
                'file',
                'mimes:pdf',
                'max:10240'
            ],
        ]);


        $equipmentInspection->highest_overall =
            $validated['highest_overall'];

        $equipmentInspection->highest_point_id =
            $validated['highest_point_id'] ?? null;

        $equipmentInspection->severity =
            $validated['severity'];

        $equipmentInspection->diagnosis =
            $validated['diagnosis'] ?? null;

        $equipmentInspection->recommendation =
            $validated['recommendation'] ?? null;


        /*
        |--------------------------------------------------------------------------
        | Upload Report PDF
        |--------------------------------------------------------------------------
        */

        if ($request->hasFile('report_file')) {

            if (
                $equipmentInspection->report_file &&
                Storage::disk('public')->exists(
                    $equipmentInspection->report_file
                )
            ) {
                Storage::disk('public')->delete(
                    $equipmentInspection->report_file
                );
            }

            $path = $request
                ->file('report_file')
                ->store(
                    'equipment-inspection-reports',
                    'public'
                );

            $equipmentInspection->report_file = $path;
        }


        $equipmentInspection->save();


        return redirect()
            ->route(
                'equipment-inspection.show',
                $equipmentInspection->id
            )
            ->with(
                'success',
                'Analysis berhasil diperbarui.'
            );
    }


    /**
     * Show operating parameters edit form.
     *
     * Admin only through route middleware.
     */
    public function editOperatingParameters(
        EquipmentInspection $equipmentInspection
    ) {
        $equipmentInspection->load([
            'inspection',
            'equipment',
        ]);

        return view(
            'equipment-inspection.operating-parameters',
            compact('equipmentInspection')
        );
    }


    /**
     * Update operating parameters.
     *
     * Admin only through route middleware.
     */
    public function updateOperatingParameters(
        Request $request,
        EquipmentInspection $equipmentInspection
    ) {
        $validated = $request->validate([

            'speed_rpm' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'suction_pressure' => [
                'nullable',
                'numeric',
            ],

            'discharge_pressure' => [
                'nullable',
                'numeric',
            ],

            'bearing_temp_m_out' => [
                'nullable',
                'numeric',
            ],

            'bearing_temp_m_in' => [
                'nullable',
                'numeric',
            ],

            'bearing_temp_p_in' => [
                'nullable',
                'numeric',
            ],

            'bearing_temp_p_out' => [
                'nullable',
                'numeric',
            ],

            'flow_rate' => [
                'nullable',
                'numeric',
                'min:0',
            ],

        ]);


        $equipmentInspection->operating_parameters = [

            'speed_rpm' =>
                $validated['speed_rpm'] ?? null,

            'suction_pressure' =>
                $validated['suction_pressure'] ?? null,

            'discharge_pressure' =>
                $validated['discharge_pressure'] ?? null,

            'bearing_temp_m_out' =>
                $validated['bearing_temp_m_out'] ?? null,

            'bearing_temp_m_in' =>
                $validated['bearing_temp_m_in'] ?? null,

            'bearing_temp_p_in' =>
                $validated['bearing_temp_p_in'] ?? null,

            'bearing_temp_p_out' =>
                $validated['bearing_temp_p_out'] ?? null,

            'flow_rate' =>
                $validated['flow_rate'] ?? null,

        ];


        $equipmentInspection->save();


        return redirect()
            ->route(
                'equipment-inspection.show',
                $equipmentInspection->id
            )
            ->with(
                'success',
                'Operating Parameters berhasil diperbarui.'
            );
    }


    /**
     * Display uploaded report PDF.
     */
    public function report(EquipmentInspection $equipmentInspection)
    {
        if (!$equipmentInspection->report_file) {
            abort(404, 'Report PDF belum tersedia.');
        }

        $disk = Storage::disk('public');

        if (!$disk->exists($equipmentInspection->report_file)) {
            abort(404, 'File Report PDF tidak ditemukan.');
        }

        return response()->file(
            $disk->path($equipmentInspection->report_file),
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' =>
                    'inline; filename="inspection-report.pdf"',
            ]
        );
    }


    /**
     * Display vibration trend.
     */
    public function trend(
        Request $request,
        EquipmentInspection $equipmentInspection
    ) {
        $equipmentInspection->load([
            'inspection',
            'equipment',
        ]);


        $measurementPoints = MeasurementPoint::where(
                'equipment_id',
                $equipmentInspection->equipment_id
            )
            ->where('active', true)
            ->orderBy('point_name')
            ->get();


        $selectedPointId =
            $request->input('measurement_point_id');


        if (
            !$selectedPointId &&
            $measurementPoints->count()
        ) {
            $selectedPointId =
                $measurementPoints->first()->id;
        }


        $selectedPoint =
            $measurementPoints->firstWhere(
                'id',
                (int) $selectedPointId
            );


        $period =
            $request->input('period', 'all');


        $allowedPeriods = [
            '7',
            '30',
            '90',
            '365',
            'all',
        ];


        if (!in_array($period, $allowedPeriods)) {
            $period = 'all';
        }


        $inspectionsQuery =
            EquipmentInspection::query()
                ->where(
                    'equipment_id',
                    $equipmentInspection->equipment_id
                )
                ->with([
                    'inspection',
                    'measurementResults.measurementPoint',
                ]);


        if ($period !== 'all') {

            $startDate =
                now()->subDays((int) $period);

            $inspectionsQuery->whereHas(
                'inspection',
                function ($query) use ($startDate) {

                    $query->whereDate(
                        'inspection_date',
                        '>=',
                        $startDate->toDateString()
                    );
                }
            );
        }


        $historicalInspections =
            $inspectionsQuery
                ->get()
                ->sortBy(function ($inspection) {

                    return optional(
                        $inspection->inspection
                    )->inspection_date;

                })
                ->values();


        $trendResults = collect();


        foreach ($historicalInspections as $inspection) {

            if (!$selectedPointId) {
                continue;
            }


            $result =
                $inspection
                    ->measurementResults
                    ->firstWhere(
                        'measurement_point_id',
                        (int) $selectedPointId
                    );


            if (!$result) {
                continue;
            }


            $trendResults->push([

                'date' =>
                    optional(
                        $inspection->inspection
                    )->inspection_date,

                'overall' =>
                    (float) $result->overall_velocity,

                'peak' =>
                    $result->peak_value !== null
                        ? (float) $result->peak_value
                        : null,

                'crest_factor' =>
                    $result->crest_factor !== null
                        ? (float) $result->crest_factor
                        : null,

                'severity' =>
                    $inspection->severity,

                'diagnosis' =>
                    $inspection->diagnosis,

            ]);
        }


        $latestResult =
            $trendResults->last();


        $latestOverall =
            $latestResult
                ? $latestResult['overall']
                : 0;


        $highestOverall =
            $trendResults->max('overall') ?? 0;


        $totalMeasurements =
            $trendResults->count();


        $trendLabels =
            $trendResults
                ->map(function ($item) {

                    if (!$item['date']) {
                        return '-';
                    }

                    return \Carbon\Carbon::parse(
                        $item['date']
                    )->format('d M Y');

                })
                ->values();


        $trendValues =
            $trendResults
                ->pluck('overall')
                ->values();


        $diagnosisTrend =
            $trendResults
                ->reverse()
                ->values();


        return view(
            'equipment-inspection.trend',
            compact(
                'equipmentInspection',
                'measurementPoints',
                'selectedPoint',
                'selectedPointId',
                'period',
                'trendResults',
                'trendLabels',
                'trendValues',
                'latestOverall',
                'highestOverall',
                'totalMeasurements',
                'diagnosisTrend'
            )
        );
    }
}