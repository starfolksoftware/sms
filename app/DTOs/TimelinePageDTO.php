<?php

namespace App\DTOs;

use Illuminate\Support\Collection;

class TimelinePageDTO
{
    public function __construct(
        public readonly Collection $events,
        public readonly ?string $nextCursor = null,
        public readonly ?string $prevCursor = null,
        public readonly bool $hasMore = false,
        public readonly bool $partial = false,
        public readonly ?string $warning = null,
        public readonly int $total = 0
    ) {}

    public function toArray(): array
    {
        return [
            'events' => $this->events->map(fn (TimelineEventDTO $event) => $event->toArray()),
            'pagination' => [
                'next_cursor' => $this->nextCursor,
                'prev_cursor' => $this->prevCursor,
                'has_more' => $this->hasMore,
            ],
            'meta' => [
                'total' => $this->total,
                'partial' => $this->partial,
                'warning' => $this->warning,
            ],
        ];
    }

    public static function empty(): self
    {
        return new self(
            events: collect(),
            hasMore: false,
            total: 0
        );
    }

    public static function partial(Collection $events, string $warning): self
    {
        return new self(
            events: $events,
            partial: true,
            warning: $warning,
            total: $events->count()
        );
    }
}
