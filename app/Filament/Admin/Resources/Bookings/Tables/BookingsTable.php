<?php

namespace App\Filament\Admin\Resources\Bookings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables;
use Filament\Tables\Table;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                //
                Tables\Columns\TextColumn::make('id')
                    ->label('Booking ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('guest.full_name')
                    ->label('Guest')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('room.room_number')
                    ->label('Room')
                    ->sortable(),

                Tables\Columns\TextColumn::make('check_in')
                    ->label('Check In')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('check_out')
                    ->label('Check Out')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nights')
                    ->label('Nights')
                    ->state(fn ($record) => 
                        \Carbon\Carbon::parse($record->check_in)
                            ->diffInDays($record->check_out)
                    ),

                Tables\Columns\TextColumn::make('total_price')
                    ->label('Total Price')
                    ->money('GHS')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_paid')
                    ->label('Total Paid')
                    ->money('GHS')
                    ->color('success'),

                Tables\Columns\TextColumn::make('balance')
                    ->label('Balance')
                    ->money('GHS')
                    ->color(fn ($record) => $record->balance > 0 ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'primary' => 'checked_in',
                        'success' => 'checked_out',
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
