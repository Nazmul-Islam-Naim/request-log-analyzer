<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rla_request_steps', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('request_id');

            // Step identity
            $table->string('name', 200);        // e.g. "middleware:Authenticate"
            $table->string('type', 50);         // middleware | controller | event | view | other

            // Timing (relative to request start)
            $table->unsignedTinyInteger('sequence')->default(0); // execution order
            $table->unsignedInteger('started_at_ms')->nullable(); // ms offset from request start
            $table->unsignedInteger('duration_ms')->nullable();

            // Arbitrary extra data per step type
            $table->json('metadata')->nullable();

            $table->timestamps();

            // ── Foreign key ────────────────────────────────────────────────────
            $table->foreign('request_id')
                ->references('id')
                ->on('rla_requests')
                ->cascadeOnDelete();

            // ── Indexes ────────────────────────────────────────────────────────
            $table->index('request_id');
            $table->index('type');
            $table->index(['request_id', 'sequence'],  'idx_steps_request_seq');
            $table->index(['request_id', 'type'],      'idx_steps_request_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rla_request_steps');
    }
};
