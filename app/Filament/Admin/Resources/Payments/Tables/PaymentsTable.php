<?php

namespace App\Filament\Admin\Resources\Payments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                //
                Tables\Columns\TextColumn::make('transaction_id')
                    ->label('Transaction ID')
                    ->state(fn (\App\Models\Payment $record): string => $record->transactionLabel())
                    ->description(fn (\App\Models\Payment $record): ?string => $record->transaction_reference ? 'Ref: '.$record->transaction_reference : null)
                    ->wrap(),

                Tables\Columns\TextColumn::make('transaction_guest')
                    ->label('Guest')
                    ->state(fn (\App\Models\Payment $record): string => $record->transactionGuestName())
                    ->description(fn (\App\Models\Payment $record): ?string => $record->transactionGuest()?->email)
                    ->wrap(),

                Tables\Columns\TextColumn::make('amount')
                    ->money('GHS')
                    ->sortable(),

                Tables\Columns\TextColumn::make('method')
                    ->badge()
                    ->colors([
                        'success' => 'cash',
                        'primary' => 'momo',
                        'warning' => 'card',
                    ]),

                Tables\Columns\TextColumn::make('transaction_reference')
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
