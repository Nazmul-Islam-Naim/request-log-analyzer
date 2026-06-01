<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rla_user_login_histories', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('login_at')->nullable();
            $table->timestamp('logout_at')->nullable();

            $table->index('login_at');
            $table->index(['user_id', 'login_at'], 'idx_login_hist_user_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rla_user_login_histories');
    }
};
