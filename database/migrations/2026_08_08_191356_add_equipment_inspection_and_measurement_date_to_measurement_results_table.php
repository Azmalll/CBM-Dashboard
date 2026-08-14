<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Add measurement_date
        |--------------------------------------------------------------------------
        */

        if (!Schema::hasColumn('measurement_results', 'measurement_date')) {

            Schema::table('measurement_results', function (Blueprint $table) {
                $table->date('measurement_date')
                    ->nullable()
                    ->after('measurement_point_id');
            });

        }


        /*
        |--------------------------------------------------------------------------
        | Unique measurement record
        |--------------------------------------------------------------------------
        |
        | Satu equipment inspection + measurement point + tanggal
        | hanya boleh memiliki satu record.
        |
        */

        Schema::table('measurement_results', function (Blueprint $table) {

            $table->unique(
                [
                    'equipment_inspection_id',
                    'measurement_point_id',
                    'measurement_date'
                ],
                'measurement_results_unique_measurement'
            );

        });
    }


    public function down(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Remove unique index
        |--------------------------------------------------------------------------
        */

        Schema::table('measurement_results', function (Blueprint $table) {

            $table->dropUnique(
                'measurement_results_unique_measurement'
            );

        });


        /*
        |--------------------------------------------------------------------------
        | Remove measurement_date
        |--------------------------------------------------------------------------
        */

        if (Schema::hasColumn('measurement_results', 'measurement_date')) {

            Schema::table('measurement_results', function (Blueprint $table) {

                $table->dropColumn('measurement_date');

            });

        }
    }
};