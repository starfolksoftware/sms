<?php

namespace App\DTOs;

use Carbon\Carbon;

class TimelineFilters
{
    public function __construct(
        public readonly array $types = ['tasks', 'deals', 'system', 'emails'],
        public readonly ?Carbon $from = null,
        public readonly ?Carbon $to = null,
        public readonly ?string $cursor = null,
        public readonly int $limit = 15
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            types: $data['types'] ?? ['tasks', 'deals', 'system', 'emails'],
            from: isset($data['from']) ? Carbon::parse($data['from']) : null,
            to: isset($data['to']) ? Carbon::parse($data['to']) : null,
            cursor: $data['cursor'] ?? null,
            limit: min($data['limit'] ?? 15, 100) // Cap at 100 for performance
        );
    }

    public function toArray(): array
    {
        return [
            'types' => $this->types,
            'from' => $this->from?->toISOString(),
            'to' => $this->to?->toISOString(),
            'cursor' => $this->cursor,
            'limit' => $this->limit,
        ];
    }

    public function hasType(string $type): bool
    {
        return in_array($type, $this->types);
    }
}
