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
        Schema::create('equipment_inspections', function (Blueprint $table) {

            $table->id();

            $table->foreignId('inspection_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('equipment_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('highest_overall', 8, 2)
                ->default(0);

            $table->foreignId('highest_point_id')
                ->nullable()
                ->constrained('measurement_points')
                ->nullOnDelete();

            $table->string('severity');

            $table->string('diagnosis')->nullable();

            $table->text('recommendation')->nullable();

            $table->string('report_file')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_inspections');
    }
};