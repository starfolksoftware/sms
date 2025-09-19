<?php

namespace App\Observers;

use App\Events\{DealCreated,DealUpdated,DealDeleted,DealRestored,DealStageChanged,DealAssigned};
use App\Models\Deal;
use App\Models\User;

class DealObserver
{
    public function created(Deal $deal): void 
    { 
        event(new DealCreated($deal)); 
    }
    
    public function updated(Deal $deal): void 
    { 
        event(new DealUpdated($deal));

        // Check if stage changed
        if ($deal->wasChanged('stage')) {
            $originalStage = $deal->getOriginal('stage');
            $newStage = $deal->stage;
            
            if ($originalStage !== $newStage) {
                event(new DealStageChanged($deal, $originalStage, $newStage));
            }
        }

        // Check if owner changed
        if ($deal->wasChanged('owner_id')) {
            $oldOwnerId = $deal->getOriginal('owner_id');
            $newOwnerId = $deal->owner_id;
            
            if ($oldOwnerId !== $newOwnerId) {
                $oldOwner = $oldOwnerId ? User::find($oldOwnerId) : null;
                $newOwner = $newOwnerId ? User::find($newOwnerId) : null;
                
                if ($newOwner) {
                    event(new DealAssigned($deal, $oldOwner, $newOwner));
                }
            }
        }
    }
    
    public function deleted(Deal $deal): void 
    { 
        event(new DealDeleted($deal)); 
    }
    
    public function restored(Deal $deal): void 
    { 
        event(new DealRestored($deal)); 
    }
}
