<?php

namespace App\Filament\Resources\WebhookEvents\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\JsonEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class WebhookEventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Webhook Details')
                    ->columns(2)
                    ->components([
                        TextInput::make('id')
                            ->label('Webhook ID')
                            ->disabled(),
                        TextInput::make('idempotency_key')
                            ->label('Idempotency Key')
                            ->disabled()
                            ->columnSpanFull(),
                        TextInput::make('event_type')
                            ->label('Event Type')
                            ->disabled(),
                        TextInput::make('source')
                            ->label('Source')
                            ->disabled(),
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'processing' => 'Processing',
                                'processed' => 'Processed',
                                'failed' => 'Failed',
                            ])
                            ->disabled(),
                        TextInput::make('attempts')
                            ->label('Attempts')
                            ->numeric()
                            ->disabled(),
                        DateTimePicker::make('received_at')
                            ->label('Received At')
                            ->disabled(),
                        DateTimePicker::make('processed_at')
                            ->label('Processed At')
                            ->disabled(),
                    ]),

                Section::make('Payload')
                    ->components([
                        JsonEditor::make('payload')
                            ->label('Raw Payload')
                            ->disabled()
                            ->columnSpanFull(),
                    ]),

                Section::make('Error Information')
                    ->visible(fn ($record) => $record && $record->status === 'failed')
                    ->components([
                        Textarea::make('error_message')
                            ->label('Error Message')
                            ->disabled()
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
