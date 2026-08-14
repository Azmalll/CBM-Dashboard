<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inspection extends Model
{
    protected $fillable = [
        'inspection_date',
        'inspector',
        'remarks',
    ];

    public function measurementResults()
    {
        return $this->hasMany(MeasurementResult::class);
    }

    public function equipmentInspections(): HasMany
    {
        return $this->hasMany(EquipmentInspection::class);
    }
}