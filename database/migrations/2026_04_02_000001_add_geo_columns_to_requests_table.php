<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rla_requests', function (Blueprint $table) {
            // GeoIP data resolved from the client IP address.
            // Both columns are nullable: unresolved IPs (private ranges,
            // lookup failures) remain NULL rather than storing empty strings.
            $table->string('country', 100)->nullable()->after('ip');
            $table->string('city', 100)->nullable()->after('country');

            // Index country for fast group-by on the dashboard chart.
            $table->index('country', 'idx_requests_country');
        });
    }

    public function down(): void
    {
        Schema::table('rla_requests', function (Blueprint $table) {
            $table->dropIndex('idx_requests_country');
            $table->dropColumn(['country', 'city']);
        });
    }
};
