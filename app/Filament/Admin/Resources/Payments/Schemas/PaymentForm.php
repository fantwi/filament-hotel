<?php

namespace App\Filament\Admin\Resources\Payments\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
                TextInput::make('amount')
                    ->numeric()
                    ->required(),

                Select::make('method')
                    ->options([
                        'cash' => 'Cash',
                        'momo' => 'Mobile Money',
                        'card' => 'Card',
                    ])
                    ->required(),

                TextInput::make('transaction_reference')
                    ->label('Reference'),
            ]);
    }
}
