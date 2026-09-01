<?php

namespace App\Filament\Admin\Resources\ConferenceRooms\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Configures Filament administration for conference rooms table.
 */
class ConferenceRoomsTable
{
    /**
     * Configures configure for the Filament administration interface.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No conference rooms found')
            ->emptyStateDescription('Create a conference room or reset the active filters to see matching event spaces.')
            ->emptyStateIcon('heroicon-o-presentation-chart-bar')
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
                TextColumn::make('id')
                    ->label('ID')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Conference Room Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->limit(80)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('capacity')
                    ->label('Capacity')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('price_per_hour')
                    ->label('Price per Hour')
                    ->money('GHS')
                    ->sortable(),

                IconColumn::make('is_available')->label('Available')->boolean(),
                IconColumn::make('is_published')->label('Published')->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_available')
                    ->label('Availability'),
                TernaryFilter::make('is_published')
                    ->label('Published'),
                SelectFilter::make('capacity')
                    ->label('Capacity')
                    ->options([
                        '1-20' => '1-20',
                        '21-50' => '21-50',
                        '51-100' => '51-100',
                        '100+' => '100+',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, string $capacity): Builder => match ($capacity) {
                            '1-20' => $query->whereBetween('capacity', [1, 20]),
                            '21-50' => $query->whereBetween('capacity', [21, 50]),
                            '51-100' => $query->whereBetween('capacity', [51, 100]),
                            '100+' => $query->where('capacity', '>', 100),
                            default => $query,
                        },
                    )),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
