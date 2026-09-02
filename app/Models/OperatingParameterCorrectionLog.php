<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperatingParameterCorrectionLog extends Model
{
    protected $fillable = [
        'user_id',
        'scope_description',
        'parameter_key',
        'stored_unit',
        'actual_unit',
        'values',
        'records_affected',
    ];

    protected $casts = [
        'values' => 'array',
    ];
}
