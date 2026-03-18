<?php

namespace App\Filament\Admin\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Filters\SelectFilter;
use Spatie\Permission\Models\Role;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                //
                Tables\Columns\TextColumn::make('name')
                    ->label('Staff Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn ($state) => str($state)->headline()->toString())
                    ->separator(',')
                    ->icon(fn ($state) => match ($state) {
                        'super_admin' => 'heroicon-o-shield-check',
                        'admin' => 'heroicon-o-shield-exclamation',
                        'manager' => 'heroicon-o-briefcase',
                        'receptionist' => 'heroicon-o-user',
                        'housekeeping' => 'heroicon-o-sparkles',
                        'accountant' => 'heroicon-o-banknotes',
                        default => 'heroicon-o-user-circle',
                    })
                    ->color(fn ($state) => match ($state) {
                        'super_admin' => 'danger',
                        'admin' => 'warning',
                        'manager' => 'secondary',
                        'receptionist' => 'success',
                        'housekeeping' => 'info',
                        'accountant' => 'gray',
                        default => 'primary'
                    }),

                // staff status
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->sortable()
                    ->badge()
                    ->icon(fn ($state) => match ($state) {
                        'online' => 'heroicon-o-check-circle',
                        'offline' => 'heroicon-o-exclamation-circle',
                        'on_leave' => 'heroicon-o-clock',
                        'suspended' => 'heroicon-o-x-circle',
                    })
                    ->color(fn ($state) => match ($state) {
                        'online' => 'success',
                        'offline' => 'danger',
                        'on_leave' => 'warning',
                        'suspended' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => str($state)->headline()),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->groups([
                Group::make('roles.name')
                ->label('Role')
                ->collapsible() // make the group collapsible
                ->formatStateUsing(fn ($state) => str($state)->headline()) // format the state using a headline
            ])
            ->filters([
                //
                SelectFilter::make('role')
                    ->label('Role')
                    ->options(
                        Role::pluck('name', 'name')
                            ->map(fn ($role) => str($role)->headline())
                    )
                    ->query(function ($query, $data) {
                        if (!empty($data['value'])) {
                            $query->role($data['value']);
                        }
                    }),

                SelectFilter::make('status')
                    ->options([
                        'online' => 'Online',
                        'offline' => 'Offline',
                        'on_leave' => 'On Leave',
                        'suspended' => 'Suspended',
                    ])
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultGroup('roles.name');
    }
}
