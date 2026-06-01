<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rla_queries', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('request_id');

            // DB context
            $table->string('connection', 50)->default('mysql');

            // SQL (store raw SQL with ? placeholders)
            $table->text('sql');
            $table->json('bindings')->nullable();    // positional binding values

            // Performance
            $table->decimal('time_ms', 10, 3);       // e.g. 12.345 ms
            $table->boolean('is_slow')->default(false);

            $table->timestamps();

            // ── Foreign key ────────────────────────────────────────────────────
            $table->foreign('request_id')
                ->references('id')
                ->on('rla_requests')
                ->cascadeOnDelete();

            // ── Indexes ────────────────────────────────────────────────────────
            $table->index('request_id');
            $table->index('connection');
            $table->index('time_ms');
            $table->index('is_slow');
            $table->index(['request_id', 'time_ms'],  'idx_queries_request_time');
            $table->index(['is_slow', 'created_at'],  'idx_queries_slow_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rla_queries');
    }
};
