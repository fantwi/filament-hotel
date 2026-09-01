<?php

namespace App\Filament\Admin\Resources\Guests\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Configures Filament administration for guests table.
 */
class GuestsTable
{
    /**
     * Configures configure for the Filament administration interface.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No guests found')
            ->emptyStateDescription('Guest profiles created through bookings or staff entry will appear here. Reset filters to show every guest.')
            ->emptyStateIcon('heroicon-o-users')
            ->emptyStateActions([
                Action::make('resetFilters')
                    ->label('Reset filters')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->action(function ($livewire): void {
                        $livewire->resetTableFilters();
                    }),
            ])
            ->columns([
                //
                // Tables\Columns\TextColumn::make('first_name')
                //     ->searchable()
                //     ->sortable(),

                // Tables\Columns\TextColumn::make('last_name')
                //     ->searchable()
                //     ->sortable(),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('Guest')
                    ->searchable(['first_name', 'last_name', 'email'])
                    ->sortable()
                    ->getStateUsing(fn ($record) => $record->first_name.' '.$record->last_name
                    ),

                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Phone Number')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('corporate_account')
                    ->label('Account type')
                    ->options([
                        'corporate' => 'Corporate-linked',
                        'personal' => 'Personal',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, string $accountType): Builder => $accountType === 'corporate'
                            ? $query->whereHas('user', fn (Builder $userQuery): Builder => $userQuery->whereNotNull('corporate_organization_id'))
                            : $query->where(fn (Builder $guestQuery): Builder => $guestQuery
                                ->whereDoesntHave('user')
                                ->orWhereHas('user', fn (Builder $userQuery): Builder => $userQuery->whereNull('corporate_organization_id'))),
                    )),
                Filter::make('created_at')
                    ->label('Joined date')
                    ->schema([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('until')->label('Until'),
                    ])
                    ->columns(2)
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['from'] ?? null,
                            fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date),
                        )
                        ->when(
                            $data['until'] ?? null,
                            fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date),
                        )),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
