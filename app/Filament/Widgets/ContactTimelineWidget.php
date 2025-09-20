<?php

namespace App\Filament\Widgets;

use App\Models\Contact;
use App\Services\ContactTimelineService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;

class ContactTimelineWidget extends Widget
{
    protected string $view = 'filament.widgets.contact-timeline';

    protected int|string|array $columnSpan = 'full';

    public ?Model $record = null;

    public array $filters = [
        'types' => ['tasks', 'deals', 'system'],
        'date_from' => null,
        'date_to' => null,
    ];

    public function mount(?Model $record = null): void
    {
        $this->record = $record;
    }

    public function getTimeline()
    {
        if (! $this->record instanceof Contact) {
            return null;
        }

        $service = new ContactTimelineService;

        return $service->getTimeline($this->record, $this->filters);
    }

    public function updateFilters(array $filters): void
    {
        $this->filters = array_merge($this->filters, array_filter($filters, fn ($value) => $value !== null));
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

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('types')
                    ->label('Event Types')
                    ->multiple()
                    ->options([
                        'tasks' => 'Tasks',
                        'deals' => 'Deals',
                        'system' => 'System Events',
                        'emails' => 'Emails',
                    ])
                    ->default(['tasks', 'deals', 'system'])
                    ->live()
                    ->afterStateUpdated(fn (array $state) => $this->updateFilters(['types' => $state])),

                DatePicker::make('date_from')
                    ->label('From Date')
                    ->live()
                    ->afterStateUpdated(fn (?string $state) => $this->updateFilters(['date_from' => $state])),

                DatePicker::make('date_to')
                    ->label('To Date')
                    ->live()
                    ->afterStateUpdated(fn (?string $state) => $this->updateFilters(['date_to' => $state])),
            ])
            ->columns(3);
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
