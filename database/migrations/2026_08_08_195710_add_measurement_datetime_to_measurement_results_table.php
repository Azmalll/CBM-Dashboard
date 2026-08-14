<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('measurement_results', 'measurement_datetime')) {
            Schema::table('measurement_results', function (Blueprint $table) {
                $table->dateTime('measurement_datetime')
                    ->nullable()
                    ->after('measurement_point_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('measurement_results', 'measurement_datetime')) {
            Schema::table('measurement_results', function (Blueprint $table) {
                $table->dropColumn('measurement_datetime');
            });
        }
    }
};