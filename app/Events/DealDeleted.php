<?php

namespace App\Events;

use App\Models\Deal;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DealDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Deal $deal) {}
}
