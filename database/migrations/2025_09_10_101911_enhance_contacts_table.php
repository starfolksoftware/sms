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
            // Add new name fields
            $table->string('first_name')->nullable()->after('id');
            $table->string('last_name')->nullable()->after('first_name');

            // Add job title
            $table->string('job_title')->nullable()->after('company');

            // Add status and source fields
            $table->string('status')->default('lead')->after('job_title');
            $table->string('source')->default('manual')->after('status');
            $table->json('source_meta')->nullable()->after('source');

            // Add owner field (different from created_by)
            $table->foreignId('owner_id')->nullable()->after('source_meta')->constrained('users')->onDelete('set null');

            // Add soft deletes
            $table->softDeletes()->after('updated_at');

            // Add indexes for performance
            $table->index(['status']);
            $table->index(['owner_id']);
            $table->index(['source']);
        });

        // Make name and email nullable
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
            $table->string('email')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['status']);
            $table->dropIndex(['source']);
            $table->dropIndex(['owner_id']);

            // Drop added columns
            $table->dropSoftDeletes();
            $table->dropForeign(['owner_id']);
            $table->dropColumn([
                'first_name',
                'last_name',
                'job_title',
                'status',
                'source',
                'source_meta',
                'owner_id',
            ]);

            // Restore email constraint
            $table->string('email')->nullable(false)->change();
        });
    }
};
