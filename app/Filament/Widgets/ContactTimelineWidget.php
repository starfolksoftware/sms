<?php

namespace App\Filament\Widgets;

use App\Models\Contact;
use App\Services\ContactTimelineService;
use Filament\Widgets\Widget;

class ContactTimelineWidget extends Widget
{
    protected string $view = 'filament.widgets.contact-timeline';

    protected int|string|array $columnSpan = 'full';

    public $record = null;

    public array $filters = [
        'types' => ['tasks', 'deals', 'system'],
        'date_from' => null,
        'date_to' => null,
    ];

    public function mount($record = null): void
    {
        $this->record = $record;
    }

    public function getTimeline()
    {
        if (! $this->record instanceof Contact) {
            return collect();
        }

        $service = new ContactTimelineService;

        return $service->getTimeline($this->record, $this->filters);
    }

    public function updateTypeFilter(array $types): void
    {
        $this->filters['types'] = $types;
        $this->dispatch('filtersUpdated');
    }

    public function updateDateFromFilter(?string $dateFrom): void
    {
        $this->filters['date_from'] = $dateFrom;
        $this->dispatch('filtersUpdated');
    }

    public function resetFilters(): void
    {
        $this->filters = [
            'types' => ['tasks', 'deals', 'system'],
            'date_from' => null,
            'date_to' => null,
        ];
        $this->dispatch('filtersReset');
    }

    public function getEventTypeColor(string $type): string
    {
        return match ($type) {
            'task' => 'blue',
            'deal' => 'green',
            'system' => 'gray',
            'email' => 'purple',
            default => 'gray',
        };
    }

    public function getEventTypeIcon(string $type): string
    {
        return match ($type) {
            'task' => 'heroicon-m-clipboard-document-list',
            'deal' => 'heroicon-m-currency-dollar',
            'system' => 'heroicon-m-cog-6-tooth',
            'email' => 'heroicon-m-envelope',
            default => 'heroicon-m-information-circle',
        };
    }
}
