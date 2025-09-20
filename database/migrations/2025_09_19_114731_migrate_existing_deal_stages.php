<?php

use App\Models\Deal;
use App\Models\DealStage;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Map old stage values to new DealStage slugs
        $stageMapping = [
            'new' => 'lead-in',
            'qualified' => 'qualified',
            'proposal' => 'proposal-sent',
            'negotiation' => 'negotiation',
            'closed' => 'closed-won', // Default to won, but we'll need to check status
        ];

        // Get all deals and update their deal_stage_id
        Deal::whereNull('deal_stage_id')->chunk(100, function ($deals) use ($stageMapping) {
            foreach ($deals as $deal) {
                $stageMappingKey = $deal->stage ?? 'new';

                // Special handling for 'closed' stage - check status
                if ($stageMappingKey === 'closed') {
                    if ($deal->status === 'lost') {
                        $targetSlug = 'closed-lost';
                    } else {
                        $targetSlug = 'closed-won';
                    }
                } else {
                    $targetSlug = $stageMapping[$stageMappingKey] ?? 'lead-in';
                }

                $dealStage = DealStage::where('slug', $targetSlug)->first();

                if ($dealStage) {
                    $deal->update(['deal_stage_id' => $dealStage->id]);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reset all deal_stage_id to null
        Deal::whereNotNull('deal_stage_id')->update(['deal_stage_id' => null]);
    }
};
