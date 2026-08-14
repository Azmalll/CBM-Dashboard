<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('measurement_results', function (Blueprint $table) {

            $table->id();

            // Inspection Session
            $table->foreignId('inspection_id')
                ->constrained('inspections')
                ->cascadeOnDelete();

            // Measurement Point
            $table->foreignId('measurement_point_id')
                ->constrained('measurement_points')
                ->cascadeOnDelete();

            // Overall vibration
            $table->decimal('overall_velocity', 8, 3)
                ->nullable();

            // Unit of measurement
            $table->string('unit')
                ->default('mm/s RMS');

            // Optional measurement information
            $table->decimal('peak_value', 8, 3)
                ->nullable();

            $table->decimal('crest_factor', 8, 3)
                ->nullable();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('measurement_results');
    }
};