<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('rla_requests');

        Schema::create('rla_requests', function (Blueprint $table) {
            $table->id();

            // Identity
            $table->string('ulid', 26)->unique();

            // HTTP
            $table->string('method', 10);
            $table->text('url');
            $table->string('uri', 1000);
            $table->text('query_string')->nullable();

            // Client
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();

            // Auth / Session
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('session_id', 100)->nullable();

            // Response
            $table->unsignedSmallInteger('status_code');
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->unsignedInteger('memory_usage_bytes')->nullable();

            // Headers (stored as JSON; sensitive values stripped by config)
            $table->json('request_headers')->nullable();
            $table->json('response_headers')->nullable();

            $table->timestamps();

            // ── Indexes ──────────────────────────────────────────────────
            $table->index('ulid');
            $table->index('method');
            $table->index('status_code');
            $table->index('user_id');
            $table->index('ip');
            $table->index('created_at');
            $table->index(['method', 'status_code'],      'idx_requests_method_status');
            $table->index(['status_code', 'created_at'],  'idx_requests_status_date');
            $table->index(['response_time_ms'],            'idx_requests_response_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rla_requests');
    }
};
