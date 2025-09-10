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
            // Optional product relationship
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();

            // Owner (salesperson) - different from created_by
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();

            // Value & currency fields
            $table->decimal('amount', 12, 2)->nullable()->after('value');
            $table->char('currency', 3)->default('USD')->after('amount');

            // Pipeline & outcome fields
            $table->string('stage')->default('new')->after('currency');
            $table->tinyInteger('probability')->nullable()->after('expected_close_date');
            $table->text('lost_reason')->nullable()->after('probability');
            $table->decimal('won_amount', 12, 2)->nullable()->after('lost_reason');
            $table->timestamp('closed_at')->nullable()->after('won_amount');

            // Attribution & meta
            $table->string('source')->nullable()->after('closed_at');
            $table->json('source_meta')->nullable()->after('source');
            $table->text('notes')->nullable()->after('source_meta');

            // Soft deletes
            $table->softDeletes();

            // Indexes for performance
            $table->index(['status']);
            $table->index(['stage', 'status']);
            $table->index(['owner_id', 'status']);
            $table->index(['contact_id']);
            $table->index(['expected_close_date']);
            $table->index(['created_at']);
            $table->index(['source']);
            $table->index(['status', 'closed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex(['status']);
            $table->dropIndex(['stage', 'status']);
            $table->dropIndex(['owner_id', 'status']);
            $table->dropIndex(['contact_id']);
            $table->dropIndex(['expected_close_date']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['source']);
            $table->dropIndex(['status', 'closed_at']);

            // Drop columns
            $table->dropSoftDeletes();
            $table->dropColumn([
                'product_id',
                'owner_id',
                'amount',
                'currency',
                'stage',
                'probability',
                'lost_reason',
                'won_amount',
                'closed_at',
                'source',
                'source_meta',
                'notes',
            ]);
        });
    }
};
