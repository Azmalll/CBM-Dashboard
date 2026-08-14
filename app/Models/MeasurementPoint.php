<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeasurementPoint extends Model
{
    protected $fillable = [
        'equipment_id',
        'point_name',
        'location',
        'direction',
        'active',
    ];

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }
    public function measurementResults()
{
    return $this->hasMany(MeasurementResult::class);
}
}