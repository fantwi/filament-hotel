<?php

namespace App\Filament\Admin\Resources\Bookings\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('amount')
                ->numeric()
                ->required()
                ->rule(function (RelationManager $livewire) {
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

            TextInput::make('transaction_reference'),
        ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('booking_id'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('amount')->money('GHS'),
                TextColumn::make('method'),
                TextColumn::make('transaction_reference'),
                TextColumn::make('created_at')->dateTime(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->visible(fn (): bool => $this->canCreate())
                    ->after(function ($record) {
                        activity()
                            ->performedOn($record)
                            ->causedBy(auth()->user())
                            ->log('payment_added');
                    }),
            ])
            ->actions([
                EditAction::make()->visible(fn (): bool => $this->canEdit()),
                DeleteAction::make()->visible(fn (): bool => $this->canDelete()),
            ])
            ->emptyStateHeading(function (RelationManager $livewire) {
                $booking = $livewire->getOwnerRecord();

                return "Remaining Balance: GHS {$booking->balance}";
            });
    }

    public function canCreate(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'accountant']);
    }

    public function canEdit(Model $record): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'accountant']);
    }

    public function canDelete(Model $record): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'accountant']);
    }
}
