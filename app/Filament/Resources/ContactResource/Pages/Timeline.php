<?php

namespace App\Filament\Resources\ContactResource\Pages;

use App\DTOs\TimelineFilters;
use App\Filament\Resources\ContactResource;
use App\Services\ContactTimelineService;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
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

    protected static string $view = 'filament.resources.contact-resource.pages.timeline';

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $title = 'Timeline';

    #[Url]
    public array $tableFilters = [
        'types' => ['tasks', 'deals', 'system'],
        'date_from' => null,
        'date_to' => null,
    ];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        // Initialize filters from URL
        $this->tableFilters = [
            'types' => request()->get('types', ['tasks', 'deals', 'system']),
            'date_from' => request()->get('date_from'),
            'date_to' => request()->get('date_to'),
        ];
    }

    public function table(Table $table): Table
    {
        $timelineService = new ContactTimelineService;

        // Convert filters to TimelineFilters DTO
        $filters = TimelineFilters::fromArray([
            'types' => $this->tableFilters['types'] ?? ['tasks', 'deals', 'system'],
            'from' => $this->tableFilters['date_from'] ? Carbon::parse($this->tableFilters['date_from']) : null,
            'to' => $this->tableFilters['date_to'] ? Carbon::parse($this->tableFilters['date_to']) : null,
            'limit' => 25,
        ]);

        // Get timeline data
        $timelineResult = $timelineService->fetch($this->record, $filters);

        return $table
            ->query(fn () => collect($timelineResult->events))
            ->paginated(false)
            ->columns([
                TextColumn::make('when')
                    ->label('When')
                    ->getStateUsing(fn ($record) => $record->timestamp->diffForHumans())
                    ->tooltip(fn ($record) => $record->timestamp->format('M j, Y g:i A'))
                    ->sortable(false),

                BadgeColumn::make('type')
                    ->label('Type')
                    ->getStateUsing(fn ($record) => ucfirst($record->type))
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
                    ->getStateUsing(fn ($record) => $record->title)
                    ->searchable(false)
                    ->limit(50),

                TextColumn::make('summary')
                    ->label('Details')
                    ->getStateUsing(fn ($record) => $record->summary)
                    ->limit(100)
                    ->placeholder('—'),

                TextColumn::make('actor')
                    ->label('Actor')
                    ->getStateUsing(fn ($record) => $record->actor['name'] ?? 'System')
                    ->placeholder('—'),
            ])
            ->actions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-m-eye')
                    ->url(fn ($record) => $record->link['url'] ?? null)
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => ! empty($record->link['url'])),
            ])
            ->emptyStateHeading('No timeline events found')
            ->emptyStateDescription('Try adjusting your filters or check back later for new activity.')
            ->emptyStateIcon('heroicon-o-clock')
            ->headerActions([
                Action::make('resetFilters')
                    ->label('Reset Filters')
                    ->color('gray')
                    ->action(function () {
                        $this->tableFilters = [
                            'types' => ['tasks', 'deals', 'system'],
                            'date_from' => null,
                            'date_to' => null,
                        ];
                        $this->redirect(static::getUrl(['record' => $this->record]));
                    }),
            ]);
    }

    public function filterForm(Form $form): Form
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
                    ->default($this->tableFilters['types'] ?? ['tasks', 'deals', 'system'])
                    ->live()
                    ->afterStateUpdated(fn (array $state) => $this->updateTableFilters(['types' => $state])),

                DatePicker::make('date_from')
                    ->label('From Date')
                    ->default($this->tableFilters['date_from'])
                    ->live()
                    ->afterStateUpdated(fn (?string $state) => $this->updateTableFilters(['date_from' => $state])),

                DatePicker::make('date_to')
                    ->label('To Date')
                    ->default($this->tableFilters['date_to'])
                    ->live()
                    ->afterStateUpdated(fn (?string $state) => $this->updateTableFilters(['date_to' => $state])),
            ])
            ->columns(3);
    }

    public function updateTableFilters(array $filters): void
    {
        $this->tableFilters = array_merge($this->tableFilters, $filters);

        // Update URL with new filters
        $queryParams = array_filter($this->tableFilters, fn ($value) => $value !== null && $value !== []);
        $this->redirect(request()->fullUrlWithQuery($queryParams));
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
