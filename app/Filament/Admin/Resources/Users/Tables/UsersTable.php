<?php

namespace App\Filament\Admin\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\TextColumn;
use Spatie\Permission\Models\Role;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                //
                TextColumn::make('name')
                    ->label('Staff Name')
                    ->searchable(['first_name', 'last_name', 'email'])
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),

                // TextColumn::make('department_label')
                //     ->label('Department')
                //     ->badge()
                //     // ->formatStateUsing(fn ($state) => str($state)->headline())
                //     ->color(fn ($state) => match ($state) {
                //         'super_admin' => 'danger',
                //         'admin' => 'warning',
                //         'reception' => 'success',
                //         'housekeeping' => 'info',
                //         'accounting' => 'secondary',
                //         'management' => 'primary',
                //         'guest' => 'gray',
                //         default => 'gray',
                //     })
                //     ->sortable(),

                // staff phone number
                TextColumn::make('phone_number')
                    ->label('Phone Number')
                    ->searchable()
                    ->sortable(),


                TextColumn::make('role_name')
                    ->label('Role')
                    ->badge()
                    ->icon(fn ($state) => match ($state) {
                        'Super Admin' => 'heroicon-o-shield-check',
                        'Admin' => 'heroicon-o-shield-exclamation',
                        'Manager' => 'heroicon-o-briefcase',
                        'Receptionist' => 'heroicon-o-user',
                        'Housekeeping' => 'heroicon-o-sparkles',
                        'Accountant' => 'heroicon-o-banknotes',
                        default => 'heroicon-o-user-circle',
                    })
                    ->color(fn ($state) => match ($state) {
                        'Super Admin' => 'danger',
                        'Admin' => 'warning',
                        'Manager' => 'secondary',
                        'Receptionist' => 'success',
                        'Housekeeping' => 'info',
                        'Accountant' => 'gray',
                        default => 'primary'
                    })
                    ->sortable(),

                // staff shift
                // TextColumn::make('shift')
                //     ->label('Shift')
                //     ->badge()
                //     ->icon(fn ($state) => match ($state) {
                //         'morning' => 'heroicon-o-sun',
                //         'evening' => 'heroicon-o-cloud',
                //         'night' => 'heroicon-o-moon',
                //         'off_duty' => 'heroicon-o-x-circle',
                //     })
                //     ->color(fn ($state) => match ($state) {
                //         'morning' => 'success',
                //         'evening' => 'warning',
                //         'night' => 'primary',
                //         'off_duty' => 'gray',
                //     })
                //     ->formatStateUsing(fn ($state) => str($state)->headline())
                //     ->sortable(),

                // staff status
                // TextColumn::make('status')
                //     ->label('Status')
                //     ->sortable()
                //     ->badge()
                //     ->icon(fn ($state) => match ($state) {
                //         'online' => 'heroicon-o-check-circle',
                //         'offline' => 'heroicon-o-exclamation-circle',
                //         'on_leave' => 'heroicon-o-clock',
                //         'suspended' => 'heroicon-o-x-circle',
                //     })
                //     ->color(fn ($state) => match ($state) {
                //         'online' => 'success',
                //         'offline' => 'danger',
                //         'on_leave' => 'warning',
                //         'suspended' => 'info',
                //         default => 'gray',
                //     })
                //     ->formatStateUsing(fn ($state) => str($state)->headline()),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->groups([
                Group::make('department')
                    ->label('Department')
                    ->collapsible(),
                    // ->defaultSort('role')
                // Group::make('roles.name')
                // ->label('Role')
                // ->collapsible() // make the group collapsible
                // ->getTitleFromRecordUsing(fn ($record) =>
                //     str(optional($record->roles->first())->name ?? 'No Role')->headline()
                // ) // get the title from the record using the role name
                // ->formatStateUsing(fn ($state) => str($state)->headline()) // format the state using a headline
            ])
            ->filters([
                //
                SelectFilter::make('department')
                    ->options(\App\Models\User::getDepartments()),

                // SelectFilter::make('role')
                //     ->label('Role')
                //     ->options(
                //         Role::pluck('name', 'name')
                //             ->map(fn ($role) => str($role)->headline())
                //     )
                //     ->query(function ($query, $data) {
                //         if (!empty($data['value'])) {
                //             $query->role($data['value']);
                //         }
                //     }),

                SelectFilter::make('status')
                    ->options([
                        'online' => 'Online',
                        'offline' => 'Offline',
                        'on_leave' => 'On Leave',
                        'suspended' => 'Suspended',
                    ]),

                    SelectFilter::make('shift')
                    ->options([
                        'morning' => 'Morning Shift',
                        'evening' => 'Evening Shift',
                        'night' => 'Night Shift',
                        'off_duty' => 'Off Duty',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultGroup('department');
    }
}
