<?php

namespace App\Filament\Admin\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

/**
 * Configures Filament administration for users table.
 */
class UsersTable
{
    /**
     * Configures configure for the Filament administration interface.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Staff Name')
                    ->searchable(['first_name', 'last_name', 'email'])
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),

                TextColumn::make('department_label')
                    ->label('Department')
                    ->badge()
                    ->placeholder('Unassigned')
                    ->color(fn (?string $state): string => match ($state) {
                        'Super Admin' => 'danger',
                        'Admin' => 'warning',
                        'Reception' => 'success',
                        'Housekeeping' => 'info',
                        'Accounting' => 'gray',
                        'Management' => 'primary',
                        'Kitchen' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(['department'])
                    ->toggleable(),

                TextColumn::make('phone_number')
                    ->label('Phone Number')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('corporateOrganization.name')
                    ->label('Corporate Account')
                    ->placeholder('Personal')
                    ->searchable()
                    ->toggleable(),

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

                TextColumn::make('shift')
                    ->label('Shift')
                    ->badge()
                    ->placeholder('Not assigned')
                    ->icon(fn (?string $state): string => match ($state) {
                        'morning' => 'heroicon-o-sun',
                        'evening' => 'heroicon-o-cloud',
                        'night' => 'heroicon-o-moon',
                        'off_duty' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-minus-circle',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'morning' => 'success',
                        'evening' => 'warning',
                        'night' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => str($state ?? 'Not assigned')->headline()->toString())
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->placeholder('Not set')
                    ->icon(fn (?string $state): string => match ($state) {
                        'online' => 'heroicon-o-check-circle',
                        'offline' => 'heroicon-o-exclamation-circle',
                        'on_leave' => 'heroicon-o-clock',
                        'suspended' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-minus-circle',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'online' => 'success',
                        'offline' => 'danger',
                        'on_leave' => 'warning',
                        'suspended' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => str($state ?? 'Not set')->headline()->toString())
                    ->sortable()
                    ->toggleable(),

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
            ])
            ->filters([
                SelectFilter::make('department')
                    ->options(User::getDepartments()),

                SelectFilter::make('corporate_organization_id')
                    ->label('Corporate Account')
                    ->relationship('corporateOrganization', 'name')
                    ->searchable()
                    ->preload(),

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
