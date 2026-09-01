<?php

namespace App\Filament\Admin\Resources\ConferenceFacilities\Tables;

use App\Filament\Admin\Resources\ConferenceFacilities\ConferenceFacilityResource;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Configures Filament administration for conference facilities table.
 */
class ConferenceFacilitiesTable
{
    /**
     * Configures configure for the Filament administration interface.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No conference facilities found')
            ->emptyStateDescription('Create a conference facility to describe the amenities available for events.')
            ->emptyStateIcon('heroicon-o-building-office-2')
            ->emptyStateActions([
                Action::make('create')
                    ->label('Create conference facility')
                    ->icon('heroicon-o-plus')
                    ->url(fn (): string => ConferenceFacilityResource::getUrl('create'))
                    ->visible(fn (): bool => ConferenceFacilityResource::canCreate()),
            ])
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
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
