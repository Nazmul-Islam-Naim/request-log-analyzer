<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Request Replay Storage ─────────────────────────────────────────
        Schema::create('rla_request_replays', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('original_request_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('method', 10)->index();
            $table->text('url');
            $table->string('uri', 1000);
            $table->text('query_string')->nullable();
            $table->text('payload')->nullable();        // JSON: request body
            $table->json('headers')->nullable();        // JSON: safe headers only
            $table->enum('status', ['pending', 'replayed', 'failed', 'archived'])->default('pending')->index();
            $table->text('last_error')->nullable();
            $table->timestamp('replayed_at')->nullable()->index();
            $table->timestamps();

            // Indexes for common queries
            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'status']);
        });

        // ── Replay Execution Log ───────────────────────────────────────────
        Schema::create('rla_replay_executions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('replay_id')->index();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->text('response_body')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('executed_at');
            $table->timestamps();

            $table->foreign('replay_id')
                ->references('id')
                ->on('rla_request_replays')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rla_replay_executions');
        Schema::dropIfExists('rla_request_replays');
    }
};
