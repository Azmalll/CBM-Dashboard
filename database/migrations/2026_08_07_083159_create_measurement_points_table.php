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
        Schema::create('measurement_points', function (Blueprint $table) {

    $table->id();

    $table->foreignId('equipment_id')
          ->constrained()
          ->onDelete('cascade');

    $table->string('point_name');

    $table->string('location');

    $table->string('direction');

    $table->boolean('active')->default(true);

    $table->timestamps();

});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('measurement_points');
    }
};
