<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Equipment extends Model
{
    protected $table = 'equipment';

    protected $fillable = [
        'equipment_id',
        'equipment_name',
        'area',
        'plant',
        'machine_type',
        'priority',
        'status',
    ];

public function measurementPoints()
{
    return $this->hasMany(MeasurementPoint::class);
}
}