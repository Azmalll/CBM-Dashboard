<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Audit log for bulk operating-parameter unit corrections.
     *
     * Each row is a single, reversible correction run:
     * who, when, scope, parameter, stored unit, actual unit,
     * the full before/after snapshot of every affected value.
     */
    public function up(): void
    {
        Schema::create('operating_parameter_correction_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('scope_description');
            $table->string('parameter_key');
            $table->string('stored_unit');
            $table->string('actual_unit');
            $table->json('values');
            $table->unsignedInteger('records_affected');
            $table->timestamp('reverted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operating_parameter_correction_logs');
    }
};
