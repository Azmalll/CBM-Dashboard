<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('measurement_results', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | Pastikan foreign key punya index sendiri
            |--------------------------------------------------------------------------
            */

            $table->index(
                'equipment_inspection_id',
                'measurement_results_equipment_inspection_index'
            );

            $table->index(
                'measurement_point_id',
                'measurement_results_measurement_point_index'
            );
        });

        Schema::table('measurement_results', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | Hapus unique index lama
            |--------------------------------------------------------------------------
            |
            | Unique lama:
            | equipment_inspection_id
            | measurement_point_id
            | measurement_date
            |
            */

            $table->dropUnique(
                'measurement_results_unique_measurement'
            );
        });

        Schema::table('measurement_results', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | Buat unique index baru
            |--------------------------------------------------------------------------
            |
            | Identitas satu pengukuran:
            |
            | Equipment Inspection
            | +
            | Measurement Point
            | +
            | Measurement DateTime
            |
            */

            $table->unique(
                [
                    'equipment_inspection_id',
                    'measurement_point_id',
                    'measurement_datetime',
                ],
                'measurement_results_unique_measurement'
            );
        });
    }

    public function down(): void
    {
        Schema::table('measurement_results', function (Blueprint $table) {

            $table->dropUnique(
                'measurement_results_unique_measurement'
            );
        });

        Schema::table('measurement_results', function (Blueprint $table) {

            $table->unique(
                [
                    'equipment_inspection_id',
                    'measurement_point_id',
                    'measurement_date',
                ],
                'measurement_results_unique_measurement'
            );
        });
    }
};