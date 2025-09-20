<?php

namespace App\Filament\Resources\WebhookEvents\Schemas;

use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\CodeEditor\Enums\Language;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
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
                        CodeEditor::make('payload')
                            ->label('Raw Payload')
                            ->language(Language::Json)
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
