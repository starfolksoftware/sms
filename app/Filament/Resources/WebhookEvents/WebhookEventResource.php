<?php

namespace App\Filament\Resources\WebhookEvents;

use App\Filament\Resources\WebhookEvents\Pages\ListWebhookEvents;
use App\Filament\Resources\WebhookEvents\Pages\ViewWebhookEvent;
use App\Filament\Resources\WebhookEvents\Schemas\WebhookEventForm;
use App\Filament\Resources\WebhookEvents\Tables\WebhookEventsTable;
use App\Models\WebhookEvent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WebhookEventResource extends Resource
{
    protected static ?string $model = WebhookEvent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Webhook Events';

    // protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 100;

    public static function form(Schema $schema): Schema
    {
        return WebhookEventForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WebhookEventsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWebhookEvents::route('/'),
            'view' => ViewWebhookEvent::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Webhook events are created by the system, not manually
    }

    public static function canDelete($record): bool
    {
        return false; // Preserve webhook history for debugging
    }
}
