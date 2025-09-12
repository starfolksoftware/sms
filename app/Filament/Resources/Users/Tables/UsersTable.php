<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Gate;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('roles.name')
                    ->label('Roles')
                    ->formatStateUsing(fn($state) => is_array($state) ? implode(', ', $state) : $state)
                    ->badge(),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'danger' => 'deactivated',
                        'success' => 'active',
                    ]),
                TextColumn::make('last_login_at')
                    ->since()
                    ->label('Last Login')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Role')
                    ->options(fn() => Role::pluck('name','name'))
                    ->query(function($query, $data) {
                        if(!$data['value']) return $query;
                        $query->whereHas('roles', fn($q) => $q->where('name',$data['value']));
                    }),
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'deactivated' => 'Deactivated',
                        'pending' => 'Pending Invite',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('assignRoles')
                        ->label('Assign Roles')
                        ->icon('heroicon-o-shield-check')
                        ->visible(fn($record) => Gate::allows('manage_roles'))
                        ->schema([
                            \Filament\Forms\Components\Select::make('roles')
                                ->multiple()
                                ->options(fn() => Role::pluck('name', 'name'))
                                ->preload()
                                ->searchable(),
                        ])
                        ->mountUsing(function ($form, $record) {
                            $form->fill([
                                'roles' => $record->roles->pluck('name')->toArray(),
                            ]);
                        })
                        ->action(function(array $data, $record): void {
                            $record->syncRoles($data['roles'] ?? []);
                        })
                        ->successNotificationTitle('Roles updated'),
                    Action::make('deactivate')
                        ->icon('heroicon-o-user-minus')
                        ->visible(fn($record) => $record->status === 'active')
                        ->requiresConfirmation()
                        ->color('danger')
                        ->action(fn($record) => $record->update(['status' => 'deactivated']))
                        ->successNotificationTitle('User deactivated'),
                    Action::make('reactivate')
                        ->icon('heroicon-o-user-check')
                        ->visible(fn($record) => $record->status === 'deactivated')
                        ->color('success')
                        ->action(fn($record) => $record->update(['status' => 'active']))
                        ->successNotificationTitle('User reactivated'),
                    Action::make('resendInvite')
                        ->icon('heroicon-o-envelope-open')
                        ->visible(fn($record) => $record->status === 'pending' && $record->invite_token)
                        ->action(function($record){ /* queue mail resend */ })
                        ->successNotificationTitle('Invite resent'),
                ]),
            ])
            ->headerActions([
                Action::make('inviteUser')
                    ->label('Invite User')
                    ->icon('heroicon-o-user-plus')
                    ->visible(fn() => Gate::allows('manage_roles'))
                    ->form([
                        \Filament\Forms\Components\TextInput::make('email')->email()->required(),
                        \Filament\Forms\Components\TextInput::make('name')->required(),
                        \Filament\Forms\Components\Select::make('roles')->multiple()->options(fn() => Role::pluck('name','name'))->preload(),
                    ])
                    ->action(function(array $data){
                        $existing = \App\Models\User::where('email',$data['email'])->first();
                        if($existing){ throw new \Exception('Email already exists.'); }
                        $token = Str::random(40);
                        $user = \App\Models\User::create([
                            'name' => $data['name'],
                            'email' => $data['email'],
                            'password' => bcrypt(Str::random(16)),
                            'status' => 'pending',
                            'invite_token' => $token,
                            'invite_token_expires_at' => now()->addDays(7),
                        ]);
                        if(!empty($data['roles'])) { $user->syncRoles($data['roles']); }
                        // dispatch invite email job placeholder
                    })
                    ->successNotificationTitle('Invite sent'),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
