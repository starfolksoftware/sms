<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            // Drop the existing unique constraint
            $table->dropUnique(['email_normalized']);
            
            // Create a partial unique index that excludes soft-deleted records
            // Note: SQLite doesn't support partial indexes well, so we'll handle this in application logic
            if (config('database.default') !== 'sqlite') {
                // For MySQL/PostgreSQL: Create partial unique index
                DB::statement('CREATE UNIQUE INDEX contacts_email_normalized_unique_active ON contacts (email_normalized) WHERE deleted_at IS NULL');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            if (config('database.default') !== 'sqlite') {
                DB::statement('DROP INDEX contacts_email_normalized_unique_active');
            }
            
            // Recreate the original unique constraint
            $table->unique('email_normalized');
        });
    }
};
