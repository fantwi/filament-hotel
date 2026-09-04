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
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                        'GHS %s per night · %d guests · %d rooms · %s',
                        number_format((float) $record->price_per_night, 2),
                        $record->capacity,
                        (int) ($record->rooms_count ?? 0),
                        $record->is_published ? 'Published' : 'Draft',
                    ))
                    ->wrap()
                    ->weight('bold')
                    ->hiddenFrom('md'),
                ImageColumn::make('image')
                    ->label('Cover')
                    ->disk('public')
                    ->visibility('public')
                    ->square()
                    ->visibleFrom('md'),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->visibleFrom('md'),
                TextColumn::make('price_per_night')
                    ->label('Nightly price')
                    ->money('GHS')
                    ->sortable()
                    ->visibleFrom('md'),
                TextColumn::make('capacity')
                    ->label('Guests')
                    ->numeric()
                    ->suffix(' guests')
                    ->sortable()
                    ->visibleFrom('md'),
                TextColumn::make('rooms_count')
                    ->label('Physical rooms')
                    ->counts('rooms')
                    ->numeric()
                    ->suffix(' rooms')
                    ->sortable()
                    ->visibleFrom('md'),
                TextColumn::make('facilities.name')
                    ->label('Facilities')
                    ->badge()
                    ->limitList(2)
                    ->expandableLimitedList()
                    ->visibleFrom('md')
                    ->toggleable(isToggledHiddenByDefault: true),
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
                TernaryFilter::make('is_published')
                    ->label('Publication status')
                    ->placeholder('All room types')
                    ->trueLabel('Published')
                    ->falseLabel('Draft'),
                SelectFilter::make('capacity')
                    ->label('Guest capacity')
                    ->options([
                        '1' => '1 guest',
                        '2' => '2 guests',
                        '3' => '3 guests',
                        '4+' => '4+ guests',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => match ((string) ($data['value'] ?? '')) {
                        '1', '2', '3' => $query->where('capacity', (int) $data['value']),
                        '4+' => $query->where('capacity', '>=', 4),
                        default => $query,
                    }),
                SelectFilter::make('facility')
                    ->label('Facility')
                    ->relationship('facilities', 'name')
                    ->searchable()
                    ->preload(),
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
            ])
            ->defaultSort('name');
    }
}
