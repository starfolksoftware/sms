<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class DataExported
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $module,
        public array $filters,
        public int $count,
        public string $format,
        public ?string $path = null,
        public ?string $exportId = null,
    ) {}
}
