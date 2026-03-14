<?php

namespace App\Filament\Admin\Resources\Bookings\RelationManagers;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\CreateAction;
// use Filament\Tables\Actions\CreateAction;
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
                TextColumn::make('amount')->money('GHS'),
                
                TextColumn::make('method'),

                // TextColumn::make('payment_date')->date(),
                
                TextColumn::make('transaction_reference'),
                
                TextColumn::make('created_at')->dateTime(),
            ])
            ->headerActions([
                CreateAction::make() // This adds create button
                    ->after(function ($record) {
                        activity()
                            ->performedOn($record)
                            ->causedBy(auth()->user())
                            ->log('payment_added');
                    }), 
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            // ->headerActions([
            //     CreateAction::make(),
            // ])
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

    // Only Admins and Accountants can create payments
    public function canCreate(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'accountant']);
    }
}
