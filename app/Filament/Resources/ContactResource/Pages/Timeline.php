<?php

namespace App\Filament\Resources\ContactResource\Pages;

use App\DTOs\TimelineFilters;
use App\Filament\Resources\ContactResource;
use App\Services\ContactTimelineService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Attributes\Url;

class Timeline extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithRecord;
    use InteractsWithTable;

    protected static string $resource = ContactResource::class;

    protected string $view = 'filament.resources.contact-resource.pages.timeline';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static ?string $title = 'Timeline';

    #[Url]
    public array $timelineFilters = [
        'types' => ['tasks', 'deals', 'system'],
        'date_from' => null,
        'date_to' => null,
    ];

    // Individual properties for form binding
    public array $types = ['tasks', 'deals', 'system'];

    public ?string $date_from = null;

    public ?string $date_to = null;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        // Initialize filters from URL
        $this->timelineFilters = [
            'types' => request()->get('types', ['tasks', 'deals', 'system']),
            'date_from' => request()->get('date_from'),
            'date_to' => request()->get('date_to'),
        ];

        // Sync individual properties
        $this->types = $this->timelineFilters['types'];
        $this->date_from = $this->timelineFilters['date_from'];
        $this->date_to = $this->timelineFilters['date_to'];
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(function () {
                $timelineService = new ContactTimelineService;

                // Convert filters to TimelineFilters DTO
                $filters = TimelineFilters::fromArray([
                    'types' => $this->types ?? ['tasks', 'deals', 'system'],
                    'from' => $this->date_from ? Carbon::parse($this->date_from) : null,
                    'to' => $this->date_to ? Carbon::parse($this->date_to) : null,
                    'limit' => 25,
                ]);

                // Get timeline data and convert to arrays immediately
                $timelineResult = $timelineService->fetch($this->record, $filters);

                return $timelineResult->events->map(fn ($event) => $event->toArray())->toArray();
            })
            ->paginated(false)
            ->columns([
                TextColumn::make('when')
                    ->label('When')
                    ->getStateUsing(fn ($record) => Carbon::parse($record['timestamp'])->diffForHumans())
                    ->tooltip(fn ($record) => Carbon::parse($record['timestamp'])->format('M j, Y g:i A'))
                    ->sortable(false),

                TextColumn::make('type')
                    ->badge()
                    ->label('Type')
                    ->getStateUsing(fn ($record) => ucfirst($record['type']))
                    ->colors([
                        'primary' => 'task',
                        'success' => 'deal',
                        'warning' => 'system',
                        'info' => 'email',
                    ])
                    ->icons([
                        'heroicon-m-clipboard-document-list' => 'task',
                        'heroicon-m-currency-dollar' => 'deal',
                        'heroicon-m-cog-6-tooth' => 'system',
                        'heroicon-m-envelope' => 'email',
                    ]),

                TextColumn::make('title')
                    ->label('Event')
                    ->getStateUsing(fn ($record) => $record['title'])
                    ->searchable(false)
                    ->limit(50),

                TextColumn::make('summary')
                    ->label('Details')
                    ->getStateUsing(fn ($record) => $record['summary'])
                    ->limit(100)
                    ->placeholder('—'),

                TextColumn::make('actor')
                    ->label('Actor')
                    ->getStateUsing(fn ($record) => $record['actor']['name'] ?? 'System')
                    ->placeholder('—'),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-m-eye')
                    ->url(fn ($record) => $record['link']['url'] ?? null)
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => ! empty($record['link']['url'])),
            ])
            ->emptyStateHeading('No timeline events found')
            ->emptyStateDescription('Try adjusting your filters or check back later for new activity.')
            ->emptyStateIcon('heroicon-o-clock')
            ->headerActions([
                Action::make('resetFilters')
                    ->label('Reset Filters')
                    ->color('gray')
                    ->action(function () {
                        $this->timelineFilters = [
                            'types' => ['tasks', 'deals', 'system'],
                            'date_from' => null,
                            'date_to' => null,
                        ];
                        $this->types = ['tasks', 'deals', 'system'];
                        $this->date_from = null;
                        $this->date_to = null;

                        // Reset the form state as well
                        $this->form->fill([
                            'types' => ['tasks', 'deals', 'system'],
                            'date_from' => null,
                            'date_to' => null,
                        ]);
                    }),
            ]);
    }

    public function filterForm(Schema $form): Schema
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
                    ->default($this->types)
                    ->live()
                    ->afterStateUpdated(function (array $state) {
                        $this->types = $state;
                        $this->timelineFilters['types'] = $state;
                    }),

                DatePicker::make('date_from')
                    ->label('From Date')
                    ->default($this->date_from)
                    ->live()
                    ->afterStateUpdated(function (?string $state) {
                        $this->date_from = $state;
                        $this->timelineFilters['date_from'] = $state;
                    }),

                DatePicker::make('date_to')
                    ->label('To Date')
                    ->default($this->date_to)
                    ->live()
                    ->afterStateUpdated(function (?string $state) {
                        $this->date_to = $state;
                        $this->timelineFilters['date_to'] = $state;
                    }),
            ])
            ->columns(3);
    }

    public static function getNavigationLabel(): string
    {
        return 'Timeline';
    }

    public function getBreadcrumb(): string
    {
        return 'Timeline';
    }
}
