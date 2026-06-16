<?php

namespace App\Filament\Admin\Resources\Guests\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables;
use Filament\Tables\Table;

class GuestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
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
                    ->getStateUsing(fn ($record) =>
                        $record->first_name . ' ' . $record->last_name
                    ),

                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Phone Number')
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
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
