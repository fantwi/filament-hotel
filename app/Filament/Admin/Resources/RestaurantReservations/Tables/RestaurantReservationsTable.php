<?php

namespace App\Filament\Admin\Resources\RestaurantReservations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class RestaurantReservationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                //
                TextColumn::make('id'),

                TextColumn::make('guest_name')
                    ->searchable(),

                TextColumn::make('table.table_number'),

                TextColumn::make('reservation_date')
                    ->date(),

                TextColumn::make('reservation_time'),

                TextColumn::make('number_of_guests'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {

                        'pending' => 'warning',

                        'confirmed' => 'info',

                        'checked_in' => 'success',

                        'completed' => 'success',

                        'cancelled' => 'danger',

                        'no_show' => 'gray',

                        default => 'secondary',

                    }),

                TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {

                        'pending' => 'warning',

                        'partial' => 'info',

                        'completed' => 'success',

                        'cancelled' => 'danger',

                        'refunded' => 'gray',

                        default => 'secondary',

                    }),

                TextColumn::make('created_at')
                    ->since()
            ])
            ->filters([
                //
                SelectFilter::make('status')

                ->options([

                    'pending' => 'Pending',

                    'confirmed' => 'Confirmed',

                    'checked_in' => 'Checked In',

                    'completed' => 'Completed',

                    'cancelled' => 'Cancelled',

                    'no_show' => 'No Show',

                ]),

                SelectFilter::make('payment_status')

                    ->options([

                        'pending' => 'Pending',

                        'partial' => 'Partial',

                        'completed' => 'Completed',

                        'cancelled' => 'Cancelled',

                        'refunded' => 'Refunded',

                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
