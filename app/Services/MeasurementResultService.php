<?php

namespace App\Services;

use App\Models\MeasurementResult;

class MeasurementResultService
{
    /**
     * Simpan hasil measurement.
     *
     * Jika equipment + measurement point + datetime
     * sudah ada → UPDATE.
     *
     * Jika belum ada → INSERT.
     */
    public function saveMeasurement(array $data): MeasurementResult
    {
        return MeasurementResult::updateOrCreate(
            [
                'equipment_inspection_id' => $data['equipment_inspection_id'],
                'measurement_point_id' => $data['measurement_point_id'],
                'measurement_datetime' => $data['measurement_datetime'],
            ],
            [
                'inspection_id' => $data['inspection_id'] ?? null,
                'measurement_date' => $data['measurement_date']
                    ?? date(
                        'Y-m-d',
                        strtotime($data['measurement_datetime'])
                    ),

                'overall_velocity' => $data['overall_velocity'] ?? null,
                'unit' => $data['unit'] ?? null,
                'peak_value' => $data['peak_value'] ?? null,
                'crest_factor' => $data['crest_factor'] ?? null,
            ]
        );
    }
}