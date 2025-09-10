<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            // Split name into first_name and last_name, keep name for compatibility
            $table->string('first_name')->nullable()->after('id');
            $table->string('last_name')->nullable()->after('first_name');
            
            // Make email nullable and add normalized version for case-insensitive uniqueness
            $table->string('email')->nullable()->change();
            $table->string('email_normalized')->nullable()->storedAs('LOWER(TRIM(email))')->after('email');
            
            // Add job title field
            $table->string('job_title')->nullable()->after('company');
            
            // Add status and source tracking
            $table->string('status')->default('lead')->after('job_title');
            $table->string('source')->default('manual')->after('status');
            $table->json('source_meta')->nullable()->after('source');
            
            // Add owner field (different from created_by)
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete()->after('source_meta');
            
            // Add soft deletes
            $table->softDeletes()->after('updated_at');
            
            // Update indexes - drop old unique on email, add new ones
            $table->dropUnique(['email']);
            $table->unique('email_normalized');
            $table->index('status');
            $table->index('owner_id');
            $table->index(['source', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            // Remove indexes first
            $table->dropUnique(['email_normalized']);
            $table->dropIndex(['status']);
            $table->dropIndex(['owner_id']);
            $table->dropIndex(['source', 'created_at']);
            
            // Restore original email unique constraint
            $table->string('email')->nullable(false)->change();
            $table->unique('email');
            
            // Remove added columns
            $table->dropSoftDeletes();
            $table->dropForeign(['owner_id']);
            $table->dropColumn([
                'first_name',
                'last_name', 
                'email_normalized',
                'job_title',
                'status',
                'source',
                'source_meta',
                'owner_id'
            ]);
        });
    }
};
