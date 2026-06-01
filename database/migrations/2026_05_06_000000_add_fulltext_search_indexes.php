<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add FULLTEXT indexes for the full-text search feature.
 *
 * Indexed columns:
 *   rla_requests.uri     — search by URL / route path
 *   rla_errors.message   — search by exception message
 *   rla_queries.sql      — search by SQL query text
 *
 * MySQL only — FULLTEXT indexes are not supported by SQLite (the fallback
 * path uses LIKE instead, so no index is needed there).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('rla_requests', function (Blueprint $table) {
            $table->fullText('uri', 'ft_requests_uri');
        });

        Schema::table('rla_errors', function (Blueprint $table) {
            $table->fullText('message', 'ft_errors_message');
        });

        Schema::table('rla_queries', function (Blueprint $table) {
            $table->fullText('sql', 'ft_queries_sql');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('rla_requests', function (Blueprint $table) {
            $table->dropFullText('ft_requests_uri');
        });

        Schema::table('rla_errors', function (Blueprint $table) {
            $table->dropFullText('ft_errors_message');
        });

        Schema::table('rla_queries', function (Blueprint $table) {
            $table->dropFullText('ft_queries_sql');
        });
    }
};
