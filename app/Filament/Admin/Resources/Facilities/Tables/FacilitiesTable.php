<?php

namespace App\Filament\Admin\Resources\Facilities\Tables;

use App\Filament\Admin\Resources\Facilities\FacilityResource;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Configures Filament administration for facilities table.
 */
class FacilitiesTable
{
    /**
     * Configures configure for the Filament administration interface.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No facilities found')
            ->emptyStateDescription('Create a facility so guests can discover the hotel amenities available to them.')
            ->emptyStateIcon('heroicon-o-building-office')
            ->emptyStateActions([
                Action::make('create')
                    ->label('Create facility')
                    ->icon('heroicon-o-plus')
                    ->url(fn (): string => FacilityResource::getUrl('create'))
                    ->visible(fn (): bool => FacilityResource::canCreate()),
            ])
            ->columns([
                //
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Facility Name')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_published')->label('Published')->boolean(),
            ])
            ->filters([
                //
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
