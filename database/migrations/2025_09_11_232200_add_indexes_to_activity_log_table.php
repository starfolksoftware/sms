<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(config('activitylog.table_name'), function (Blueprint $table): void {
            $table->index(['created_at', 'log_name'], 'activity_log_created_log');
            $table->index(['subject_type', 'subject_id'], 'activity_log_subject');
        });
    }

    public function down(): void
    {
        Schema::table(config('activitylog.table_name'), function (Blueprint $table): void {
            $table->dropIndex('activity_log_created_log');
            $table->dropIndex('activity_log_subject');
        });
    }
};
