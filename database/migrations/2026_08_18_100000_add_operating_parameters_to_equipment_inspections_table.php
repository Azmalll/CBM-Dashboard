<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add operating parameters to each equipment inspection.
     */
    public function up(): void
    {
        Schema::table('equipment_inspections', function (Blueprint $table) {

            $table->json('operating_parameters')
                ->nullable()
                ->after('report_file');

        });
    }

    /**
     * Remove operating parameters.
     */
    public function down(): void
    {
        Schema::table('equipment_inspections', function (Blueprint $table) {

            $table->dropColumn('operating_parameters');

        });
    }
};