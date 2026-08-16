<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove legacy measurement_date unique index if it still exists.
     */
    public function up(): void
    {
        $indexes = DB::select("
            SHOW INDEX
            FROM measurement_results
            WHERE Key_name = 'measurement_results_equipment_inspection_id_measurement_point_id_measurement_date_unique'
        ");

        if (!empty($indexes)) {
            Schema::table('measurement_results', function (Blueprint $table) {
                $table->dropUnique(
                    'measurement_results_equipment_inspection_id_measurement_point_id_measurement_date_unique'
                );
            });
        }
    }

    /**
     * Restore the legacy unique index if migration is rolled back.
     */
    public function down(): void
    {
        $indexes = DB::select("
            SHOW INDEX
            FROM measurement_results
            WHERE Key_name = 'measurement_results_equipment_inspection_id_measurement_point_id_measurement_date_unique'
        ");

        if (empty($indexes)) {
            Schema::table('measurement_results', function (Blueprint $table) {
                $table->unique(
                    [
                        'equipment_inspection_id',
                        'measurement_point_id',
                        'measurement_date',
                    ],
                    'measurement_results_equipment_inspection_id_measurement_point_id_measurement_date_unique'
                );
            });
        }
    }
};