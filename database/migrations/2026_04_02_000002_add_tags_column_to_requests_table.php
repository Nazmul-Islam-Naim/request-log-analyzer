<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rla_requests', function (Blueprint $table) {
            // JSON array of user-defined tags, e.g. ["payment", "checkout"].
            // Stored on the requests row so it is filterable with a single query.
            $table->json('tags')->nullable()->after('response_headers');
        });
    }

    public function down(): void
    {
        Schema::table('rla_requests', function (Blueprint $table) {
            $table->dropColumn('tags');
        });
    }
};
