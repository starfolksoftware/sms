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
            // Add new columns that don't exist
            $table->foreignId('product_id')->nullable()->after('contact_id')->constrained('products')->nullOnDelete();
            $table->foreignId('owner_id')->nullable()->after('product_id')->constrained('users', 'id')->nullOnDelete();
            
            // Rename 'value' to 'amount' 
            $table->renameColumn('value', 'amount');
            
            // Add currency and enhanced fields
            $table->char('currency', 3)->default('USD')->after('amount');
            $table->string('stage')->default('new')->after('currency');
            $table->unsignedTinyInteger('probability')->nullable()->after('expected_close_date');
            $table->text('lost_reason')->nullable()->after('probability');
            $table->decimal('won_amount', 12, 2)->nullable()->after('lost_reason');
            $table->timestamp('closed_at')->nullable()->after('won_amount');
            
            // Attribution & meta
            $table->string('source')->default('manual')->after('closed_at');
            $table->json('source_meta')->nullable()->after('source');
            $table->text('notes')->nullable()->after('source_meta');
            
            // Add soft deletes
            $table->softDeletes();
            
            // Add indexes for performance
            $table->index('status');
            $table->index(['stage','status']);
            $table->index(['owner_id','status']);
            $table->index('contact_id');
            $table->index('expected_close_date');
            $table->index(['status','closed_at']);
            $table->index('source');
            $table->index('created_at');
            $table->index('product_id');
        });
        
        // Update status enum to include new values if needed
        DB::statement("ALTER TABLE deals MODIFY COLUMN status ENUM('open', 'won', 'lost') DEFAULT 'open'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex(['status']);
            $table->dropIndex(['stage','status']);
            $table->dropIndex(['owner_id','status']);
            $table->dropIndex(['contact_id']);
            $table->dropIndex(['expected_close_date']);
            $table->dropIndex(['status','closed_at']);
            $table->dropIndex(['source']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['product_id']);
            
            // Drop soft deletes
            $table->dropSoftDeletes();
            
            // Drop new columns
            $table->dropColumn([
                'product_id', 'owner_id', 'currency', 'stage', 
                'probability', 'lost_reason', 'won_amount', 'closed_at',
                'source', 'source_meta', 'notes'
            ]);
            
            // Rename amount back to value
            $table->renameColumn('amount', 'value');
        });
    }
};