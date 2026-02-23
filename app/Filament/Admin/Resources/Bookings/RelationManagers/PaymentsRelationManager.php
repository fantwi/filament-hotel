<?php

namespace App\Filament\Admin\Resources\Bookings\RelationManagers;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Get;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
                TextInput::make('amount')
                    ->numeric()
                    ->required()
                    ->rule(function (RelationManager $livewire){
                        return function (string $attribute, $value, \Closure $fail) use ($livewire) {
                            $booking = $livewire->getOwnerRecord();
                            if ($value > $booking->balance) {
                                $fail("Payment exceeds remaining balance of GHS {$booking->balance}.");
                            }
                        };
                    }),

                Select::make('method')
                    ->options([
                        'cash' => 'Cash',
                        'momo' => 'Mobile Money',
                        'card' => 'Card',
                    ])
                    ->required(),

                // DatePicker::make('payment_date')
                //     ->required(),

                TextInput::make('transaction_reference'),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('booking_id'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('amount')->money('GHS'),
                
                Tables\Columns\TextColumn::make('method'),

                // TextColumn::make('payment_date')->date(),
                
                Tables\Columns\TextColumn::make('transaction_reference'),
                
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make(), // This adds create button
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make(),
            ])
            ->emptyStateHeading(function (RelationManager $livewire) {
                $booking = $livewire->getOwnerRecord();
                return "Remaining Balance: GHS {$booking->balance}";
            });
            // ->actions([
            //     ViewAction::make(),
            // ]);
            // ->filters([
            //     //
            // ])
            // ->headerActions([
            //     CreateAction::make(),
            //     AssociateAction::make(),
            // ])
            // ->recordActions([
            //     ViewAction::make(),
            //     EditAction::make(),
            //     DissociateAction::make(),
            //     DeleteAction::make(),
            // ])
            // ->toolbarActions([
            //     BulkActionGroup::make([
            //         DissociateBulkAction::make(),
            //         DeleteBulkAction::make(),
            //     ]),
            // ]);
    }
    // public function canCreate(): bool
    // {
    //     return true;
    // }
}
