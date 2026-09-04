<?php

namespace App\Filament\Admin\Resources\RoomTypes\Tables;

use App\Filament\Admin\Resources\RoomTypes\RoomTypeResource;
use App\Models\RoomType;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

/**
 * Configures Filament administration for room types table.
 */
class RoomTypesTable
{
    /**
     * Configures configure for the Filament administration interface.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->emptyStateHeading(fn (HasTable $livewire): string => $livewire->hasTableSearch()
                ? 'No room types match your search'
                : 'No room types found')
            ->emptyStateDescription(fn (HasTable $livewire): string => $livewire->hasTableSearch()
                ? 'Clear the search to return to all room types.'
                : 'Create a room type to define the accommodations guests can book.')
            ->emptyStateIcon(fn (HasTable $livewire): string => $livewire->hasTableSearch()
                ? 'heroicon-o-magnifying-glass'
                : 'heroicon-o-home-modern')
            ->emptyStateActions([
                Action::make('clearSearch')
                    ->label('Clear search')
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->visible(fn (HasTable $livewire): bool => $livewire->hasTableSearch())
                    ->action(fn (HasTable $livewire) => $livewire->resetTableSearch()),
                Action::make('create')
                    ->label('Create room type')
                    ->icon('heroicon-o-plus')
                    ->url(fn (): string => RoomTypeResource::getUrl('create'))
                    ->visible(fn (HasTable $livewire): bool => ! $livewire->hasTableSearch() && RoomTypeResource::canCreate()),
            ])
            ->columns([
                TextColumn::make('mobile_summary')
                    ->label('Room type')
                    ->state(fn (RoomType $record): string => $record->name)
                    ->description(fn (RoomType $record): string => sprintf(
                        'GHS %s per night · Capacity %d · %s',
                        number_format((float) $record->price_per_night, 2),
                        $record->capacity,
                        $record->is_published ? 'Published' : 'Draft',
                    ))
                    ->wrap()
                    ->weight('bold')
                    ->hiddenFrom('md'),
                TextColumn::make('name')
                    ->searchable()
                    ->visibleFrom('md'),
                TextColumn::make('price_per_night')
                    ->numeric()
                    ->sortable()
                    ->visibleFrom('md'),
                TextColumn::make('capacity')
                    ->numeric()
                    ->sortable()
                    ->visibleFrom('md'),
                IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean()
                    ->visibleFrom('md'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->visibleFrom('md')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->visibleFrom('md')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->modalDescription('Only unused room types will be deleted. Unpublish room types assigned to rooms instead.')
                        ->authorizeIndividualRecords(
                            fn (RoomType $record): bool => RoomTypeResource::canDelete($record)
                        )
                        ->missingBulkAuthorizationFailureNotificationMessage(
                            fn (int $failureCount): string => $failureCount === 1
                                ? 'One room type was not deleted because it is assigned to a room. Unpublish it instead.'
                                : "{$failureCount} room types were not deleted because they are assigned to rooms. Unpublish them instead."
                        ),
                ]),
            ]);
    }
}
