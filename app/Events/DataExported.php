<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DataExported
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $module,
        public array $filters,
        public int $recordCount,
        public string $format,
        public ?string $exportPath = null,
    ) {}
}
