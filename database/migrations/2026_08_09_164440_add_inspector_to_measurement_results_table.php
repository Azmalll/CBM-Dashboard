<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('measurement_results', function (Blueprint $table) {
            $table->string('inspector')
                ->nullable()
                ->after('measurement_datetime');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('measurement_results', function (Blueprint $table) {
            $table->dropColumn('inspector');
        });
    }
};