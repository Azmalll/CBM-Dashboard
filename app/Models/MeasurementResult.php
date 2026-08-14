<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MeasurementResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'inspection_id',
        'equipment_inspection_id',
        'measurement_point_id',

        // Waktu pengukuran lengkap
        'measurement_datetime',

        // Tetap dipertahankan untuk kompatibilitas data lama
        'measurement_date',

        // Inspector yang melakukan measurement
        'inspector',

        'overall_velocity',
        'unit',
        'peak_value',
        'crest_factor',
    ];

    protected $casts = [
        'measurement_datetime' => 'datetime',
        'measurement_date' => 'date',

        'overall_velocity' => 'decimal:3',
        'peak_value' => 'decimal:3',
        'crest_factor' => 'decimal:3',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    public function inspection()
    {
        return $this->belongsTo(Inspection::class);
    }

    public function equipmentInspection()
    {
        return $this->belongsTo(
            EquipmentInspection::class,
            'equipment_inspection_id'
        );
    }

    public function measurementPoint()
    {
        return $this->belongsTo(
            MeasurementPoint::class,
            'measurement_point_id'
        );
    }
}