<?php

namespace App\Http\Controllers;

use App\Models\EquipmentInspection;
use App\Models\MeasurementResult;
use Illuminate\Http\Request;

class VibrationTrendController extends Controller
{
    /**
     * Display vibration trend for an equipment.
     */
    public function index(
        Request $request,
        EquipmentInspection $equipmentInspection
    ) {
        $equipmentInspection->load([
            'equipment',
            'inspection'
        ]);

        // =====================================================
        // MEASUREMENT POINTS AVAILABLE FOR THIS EQUIPMENT
        // =====================================================

        $measurementPoints = MeasurementResult::with(
            'measurementPoint'
        )
        ->whereHas(
            'equipmentInspection',
            function ($query) use ($equipmentInspection) {
                $query->where(
                    'equipment_id',
                    $equipmentInspection->equipment_id
                );
            }
        )
        ->get()
        ->pluck('measurementPoint')
        ->filter()
        ->unique('id')
        ->sortBy('point_name')
        ->values();


        // =====================================================
        // SELECTED MEASUREMENT POINT
        // =====================================================

        $selectedPointId = $request->input(
            'measurement_point_id'
        );


        if (
            !$selectedPointId &&
            $measurementPoints->isNotEmpty()
        ) {
            $selectedPointId =
                $measurementPoints->first()->id;
        }


        // =====================================================
        // TREND DATA
        // =====================================================

        $results = MeasurementResult::with([
            'measurementPoint',
            'equipmentInspection.inspection'
        ])
        ->whereHas(
            'equipmentInspection',
            function ($query) use ($equipmentInspection) {
                $query->where(
                    'equipment_id',
                    $equipmentInspection->equipment_id
                );
            }
        )
        ->when(
            $selectedPointId,
            function ($query) use ($selectedPointId) {
                $query->where(
                    'measurement_point_id',
                    $selectedPointId
                );
            }
        )
        ->orderBy('created_at')
        ->get();


        // =====================================================
        // CHART DATA
        // =====================================================

        $chartLabels = $results
            ->map(function ($result) {
                return $result->created_at
                    ? $result->created_at->format('d M Y')
                    : '-';
            })
            ->values();


        $chartValues = $results
            ->map(function ($result) {
                return (float) $result->overall_velocity;
            })
            ->values();


        // =====================================================
        // SELECTED POINT
        // =====================================================

        $selectedPoint = $measurementPoints->firstWhere(
            'id',
            (int) $selectedPointId
        );


        return view(
            'vibration-trend.index',
            compact(
                'equipmentInspection',
                'measurementPoints',
                'selectedPointId',
                'selectedPoint',
                'results',
                'chartLabels',
                'chartValues'
            )
        );
    }
}