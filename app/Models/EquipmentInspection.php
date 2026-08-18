<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EquipmentInspection extends Model
{
    protected $fillable = [
        'inspection_id',
        'equipment_id',
        'highest_overall',
        'highest_point_id',
        'severity',
        'diagnosis',
        'recommendation',
        'report_file',
        'operating_parameters',
    ];

    protected $casts = [
        'operating_parameters' => 'array',
    ];

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function highestPoint(): BelongsTo
    {
        return $this->belongsTo(
            MeasurementPoint::class,
            'highest_point_id'
        );
    }

    public function measurementResults(): HasMany
    {
        return $this->hasMany(
            MeasurementResult::class,
            'equipment_inspection_id'
        );
    }
}