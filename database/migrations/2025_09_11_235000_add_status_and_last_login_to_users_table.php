<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users','status')) {
                $table->string('status')->default('active');
            }
            if (!Schema::hasColumn('users','last_login_at')) {
                $table->timestamp('last_login_at')->nullable();
            }
            if (!Schema::hasColumn('users','invite_token')) {
                $table->string('invite_token')->nullable()->unique();
                $table->timestamp('invite_token_expires_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['status','last_login_at','invite_token','invite_token_expires_at']);
        });
    }
};
