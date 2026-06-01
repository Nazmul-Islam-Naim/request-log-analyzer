<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── API Rate Usage Tracking ───────────────────────────────────────
        Schema::create('rla_api_rate_usage', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip', 45)->index();          // Support IPv4 and IPv6
            $table->string('endpoint')->nullable();     // API endpoint or URI
            $table->unsignedInteger('request_count')->default(0);
            $table->timestamp('first_request_at')->nullable();
            $table->timestamp('last_request_at')->nullable();
            $table->boolean('rate_limit_exceeded')->default(false);
            $table->string('period_type')->default('minute');  // minute, hour, day
            $table->timestamps();

            // Composite indexes for efficient queries
            $table->index(['user_id', 'period_type']);
            $table->index(['ip', 'period_type']);
            $table->index(['rate_limit_exceeded', 'period_type']);
            $table->index('last_request_at');
        });

        // ── Rate Limit Incidents (violations log) ────────────────────────
        Schema::create('rla_rate_limit_incidents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip', 45)->index();
            $table->string('endpoint')->nullable();
            $table->unsignedInteger('request_count');
            $table->unsignedInteger('limit_threshold');
            $table->string('incident_type');  // user_limit, ip_limit
            $table->timestamp('detected_at')->index();
            $table->timestamp('cleared_at')->nullable();
            $table->boolean('resolved')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes for filtering
            $table->index(['incident_type', 'detected_at']);
            $table->index(['resolved', 'detected_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rla_rate_limit_incidents');
        Schema::dropIfExists('rla_api_rate_usage');
    }
};
