<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add indexes that were missing from the initial migrations.
 *
 * rla_requests
 *   - (user_id, created_at)  composite — covers active-users / userRouteHits WHERE clauses
 *   - uri(200)               prefix    — covers GROUP BY uri in topRoutes / analytics / dashboard
 *
 * rla_queries
 *   - created_at             scalar    — covers date-range filters on slow-query lookups
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── rla_requests ──────────────────────────────────────────────────────
        Schema::table('rla_requests', function (Blueprint $table) {
            // Composite: drives active-users window query and userRouteHits filter.
            // Both columns are already indexed separately; the composite is the
            // covering index that lets MySQL satisfy the full WHERE in one pass.
            $table->index(
                ['user_id', 'created_at'],
                'idx_requests_user_created'
            );
        });

        // Prefix index on uri(200) — Blueprint doesn't support prefix lengths
        // directly, so we fall back to a raw DDL statement.
        // 200 chars covers the vast majority of real-world route URIs and fits
        // well within InnoDB's 3072-byte page limit (utf8mb4 × 200 = 800 bytes).
        DB::statement(
            'CREATE INDEX idx_requests_uri_prefix ON rla_requests (uri(200))'
        );

        // ── rla_queries ───────────────────────────────────────────────────────
        Schema::table('rla_queries', function (Blueprint $table) {
            // Needed when filtering slow queries by date range.
            $table->index('created_at', 'idx_queries_created_at');
        });
    }

    public function down(): void
    {
        Schema::table('rla_requests', function (Blueprint $table) {
            $table->dropIndex('idx_requests_user_created');
        });

        DB::statement('DROP INDEX idx_requests_uri_prefix ON rla_requests');

        Schema::table('rla_queries', function (Blueprint $table) {
            $table->dropIndex('idx_queries_created_at');
        });
    }
};
