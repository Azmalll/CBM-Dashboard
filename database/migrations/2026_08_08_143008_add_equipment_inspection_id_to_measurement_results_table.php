<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('measurement_results', function (Blueprint $table) {
            $table->foreignId('equipment_inspection_id')
                ->nullable()
                ->after('inspection_id')
                ->constrained('equipment_inspections')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('measurement_results', function (Blueprint $table) {
            $table->dropForeign(['equipment_inspection_id']);
            $table->dropColumn('equipment_inspection_id');
        });
    }
};