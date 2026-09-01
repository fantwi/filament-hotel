<?php

namespace App\Filament\Admin\Resources\Rooms\Tables;

use App\Filament\Admin\Resources\Rooms\RoomResource;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Configures Filament administration for rooms table.
 */
class RoomsTable
{
    /**
     * Configures configure for the Filament administration interface.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No rooms found')
            ->emptyStateDescription('Create a room to make accommodation inventory available for booking.')
            ->emptyStateIcon('heroicon-o-home')
            ->emptyStateActions([
                Action::make('create')
                    ->label('Create room')
                    ->icon('heroicon-o-plus')
                    ->url(fn (): string => RoomResource::getUrl('create'))
                    ->visible(fn (): bool => RoomResource::canCreate()),
            ])
            ->columns([
                //
                Tables\Columns\TextColumn::make('room_number')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('roomType.name')
                    ->label('Room Type')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'available',
                        'danger' => 'occupied',
                        'warning' => 'maintenance',
                    ]),
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
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
