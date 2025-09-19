<?php

namespace App\Events;

use App\Models\Deal;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DealAssigned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Deal $deal,
        public ?User $oldOwner,
        public User $newOwner
    ) {}
}