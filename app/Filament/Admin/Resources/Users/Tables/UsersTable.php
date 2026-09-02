<?php

namespace App\Filament\Admin\Resources\Users\Tables;

use App\Enums\StaffAccountStatus;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
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
            ->emptyStateHeading('No staff users found')
            ->emptyStateDescription('Create a staff user or reset filters to manage team access.')
            ->emptyStateIcon('heroicon-o-user-group')
            ->emptyStateActions([
                Action::make('create')
                    ->label('Create staff user')
                    ->icon('heroicon-o-plus')
                    ->url(fn (): string => UserResource::getUrl('create'))
                    ->visible(fn (): bool => UserResource::canCreate()),
                Action::make('resetFilters')
                    ->label('Reset filters')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->action(function ($livewire): void {
                        $livewire->resetTableFiltersForm();
                    }),
            ])
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

                ViewColumn::make('status')
                    ->label('Status')
                    ->view('filament.admin.components.staff-status')
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
                    ->options(StaffAccountStatus::options()),

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
