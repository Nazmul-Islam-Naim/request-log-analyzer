<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rla_errors', function (Blueprint $table) {
            $table->id();

            // Nullable: errors can occur outside a request context (e.g. queue jobs)
            $table->unsignedBigInteger('request_id')->nullable();

            // Exception details
            $table->string('exception_class', 255);
            $table->text('message');
            $table->text('file');
            $table->unsignedInteger('line');
            $table->longText('trace')->nullable();

            // Structured context data (extra vars passed to report())
            $table->json('context')->nullable();

            // PSR-3 compatible severity level
            $table->enum('severity', [
                'debug', 'info', 'notice', 'warning',
                'error', 'critical', 'alert', 'emergency',
            ])->default('error');

            $table->timestamps();

            // ── Foreign key ────────────────────────────────────────────────────
            $table->foreign('request_id')
                ->references('id')
                ->on('rla_requests')
                ->nullOnDelete();

            // ── Indexes ────────────────────────────────────────────────────────
            $table->index('request_id');
            $table->index('exception_class');
            $table->index('severity');
            $table->index('created_at');
            $table->index(['severity', 'created_at'],      'idx_errors_severity_date');
            $table->index(['exception_class', 'severity'], 'idx_errors_class_severity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rla_errors');
    }
};
