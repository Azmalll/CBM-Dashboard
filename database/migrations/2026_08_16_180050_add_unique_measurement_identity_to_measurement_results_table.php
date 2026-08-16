<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('measurement_results', function (Blueprint $table) {

            $table->unique(
                [
                    'equipment_inspection_id',
                    'measurement_point_id',
                    'measurement_datetime',
                ],
                'measurement_results_identity_unique'
            );

        });
    }

    public function down(): void
    {
        Schema::table('measurement_results', function (Blueprint $table) {

            $table->dropUnique(
                'measurement_results_identity_unique'
            );

        });
    }
};