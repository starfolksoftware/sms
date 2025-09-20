<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add indexes for timeline performance optimizations

        // Tasks table indexes for timeline queries
        Schema::table('tasks', function (Blueprint $table) {
            // Compound index for contact timeline queries
            $table->index(['contact_id', 'updated_at'], 'tasks_contact_timeline_idx');
            $table->index(['contact_id', 'created_at'], 'tasks_contact_created_idx');
        });

        // Deals table indexes for timeline queries
        Schema::table('deals', function (Blueprint $table) {
            // Compound index for contact timeline queries
            $table->index(['contact_id', 'updated_at'], 'deals_contact_timeline_idx');
            $table->index(['contact_id', 'created_at'], 'deals_contact_created_idx');
            $table->index(['contact_id', 'closed_at'], 'deals_contact_closed_idx');
        });

        // Activity log indexes for system events
        Schema::table('activity_log', function (Blueprint $table) {
            // Compound index for subject-based timeline queries
            $table->index(['subject_type', 'subject_id', 'created_at'], 'activity_log_subject_timeline_idx');
        });

        // Future: Email event table indexes (when implemented)
        // These would be created when email tracking tables are added:
        // - email_events: (contact_id, occurred_at)
        // - email_opens: (contact_id, opened_at)
        // - email_clicks: (contact_id, clicked_at)
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_contact_timeline_idx');
            $table->dropIndex('tasks_contact_created_idx');
        });

        Schema::table('deals', function (Blueprint $table) {
            $table->dropIndex('deals_contact_timeline_idx');
            $table->dropIndex('deals_contact_created_idx');
            $table->dropIndex('deals_contact_closed_idx');
        });

        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropIndex('activity_log_subject_timeline_idx');
        });
    }
};
