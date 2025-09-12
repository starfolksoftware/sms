<?php

namespace App\Observers;

use App\Events\{DealCreated,DealUpdated,DealDeleted,DealRestored};
use App\Models\Deal;

class DealObserver
{
    public function created(Deal $deal): void { event(new DealCreated($deal)); }
    public function updated(Deal $deal): void { event(new DealUpdated($deal)); }
    public function deleted(Deal $deal): void { event(new DealDeleted($deal)); }
    public function restored(Deal $deal): void { event(new DealRestored($deal)); }
}
