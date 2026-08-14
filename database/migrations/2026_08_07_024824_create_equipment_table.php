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
    Schema::create('equipment', function (Blueprint $table) {
        $table->id();

        $table->string('equipment_id')->unique();
        $table->string('equipment_name');

        $table->string('area');
        $table->string('plant');

        $table->string('machine_type');

        $table->enum('priority', [
            'Low',
            'Medium',
            'High',
            'Critical'
        ]);

        $table->enum('status', [
            'Normal',
            'Alert',
            'Danger'
        ])->default('Normal');

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
