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
        Schema::table('deals', function (Blueprint $table) {
            $table->foreignId('deal_stage_id')->nullable()->after('stage')->constrained('deal_stages')->nullOnDelete();
            $table->index('deal_stage_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->dropForeign(['deal_stage_id']);
            $table->dropIndex(['deal_stage_id']);
            $table->dropColumn('deal_stage_id');
        });
    }
};
