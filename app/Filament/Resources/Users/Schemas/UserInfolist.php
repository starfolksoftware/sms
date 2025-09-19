<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(12)->schema([
                Section::make('Identity')
                    ->schema([
                        TextEntry::make('name')->weight('bold')->size('lg'),
                        TextEntry::make('email')->label('Email')->icon('heroicon-m-envelope')->copyable(),
                        TextEntry::make('status')->badge()->colors([
                            'success' => 'active',
                            'warning' => 'pending',
                            'danger' => 'deactivated',
                        ])->label('Status'),
                    ])->columns(3)->columnSpan(8),
                Section::make('Roles & Access')
                    ->schema([
                        TextEntry::make('roles.name')
                            ->label('Roles')
                            ->badge()
                            ->separator(','),
                        TextEntry::make('email_verified_at')->dateTime()->label('Verified')->placeholder('-'),
                        TextEntry::make('last_login_at')->since()->label('Last Login')->placeholder('-'),
                    ])->columns(2)->columnSpan(4),
                Section::make('Meta')
                    ->schema([
                        TextEntry::make('invite_token')->label('Invite Token')->copyable()->limit(16)->placeholder('-'),
                        TextEntry::make('invite_token_expires_at')->dateTime()->label('Invite Expires')->placeholder('-'),
                        TextEntry::make('created_at')->dateTime()->label('Created'),
                        TextEntry::make('updated_at')->since()->label('Updated'),
                    ])->columns(2)->columnSpanFull()->collapsed()->collapsible(),
            ])->columnSpanFull(),
        ]);
    }
}
