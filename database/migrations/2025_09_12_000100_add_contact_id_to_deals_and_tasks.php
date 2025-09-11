<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('deals') && ! Schema::hasColumn('deals','contact_id')) {
            Schema::table('deals', function (Blueprint $table): void {
                $table->foreignId('contact_id')->nullable()->after('stage')->constrained('contacts')->nullOnDelete();
            });
        }

        if (Schema::hasTable('tasks') && ! Schema::hasColumn('tasks','contact_id')) {
            Schema::table('tasks', function (Blueprint $table): void {
                $table->foreignId('contact_id')->nullable()->after('creator_id')->constrained('contacts')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('deals') && Schema::hasColumn('deals','contact_id')) {
            Schema::table('deals', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('contact_id');
            });
        }
        if (Schema::hasTable('tasks') && Schema::hasColumn('tasks','contact_id')) {
            Schema::table('tasks', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('contact_id');
            });
        }
    }
};
