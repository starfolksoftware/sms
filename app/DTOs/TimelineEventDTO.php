<?php

namespace App\DTOs;

use Carbon\Carbon;

class TimelineEventDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly string $subtype,
        public readonly Carbon $timestamp,
        public readonly ?array $actor,
        public readonly string $title,
        public readonly ?string $summary,
        public readonly ?array $link,
        public readonly ?array $metadata
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'subtype' => $this->subtype,
            'timestamp' => $this->timestamp->toISOString(),
            'actor' => $this->actor,
            'title' => $this->title,
            'summary' => $this->summary,
            'link' => $this->link,
            'metadata' => $this->metadata,
        ];
    }
}
