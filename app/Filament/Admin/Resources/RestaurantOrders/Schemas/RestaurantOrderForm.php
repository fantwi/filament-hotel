<?php

namespace App\Filament\Admin\Resources\RestaurantOrders\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RestaurantOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Order')
                ->schema([
                    TextInput::make('order_number')->required()->unique(ignoreRecord: true),
                    Select::make('guest_id')->relationship('guest', 'email')->searchable()->preload(),
                    Select::make('restaurant_reservation_id')->relationship('reservation', 'id')->searchable()->preload(),
                    Select::make('status')->options([
                        'pending' => 'Pending', 'confirmed' => 'Confirmed', 'preparing' => 'Preparing',
                        'ready' => 'Ready', 'served' => 'Served', 'cancelled' => 'Cancelled',
                    ])->required(),
                    Select::make('payment_status')->options([
                        'pending' => 'Pending', 'completed' => 'Completed', 'failed' => 'Failed', 'refunded' => 'Refunded',
                    ])->required(),
                    TextInput::make('subtotal')->numeric()->prefix('GHS')->required(),
                    TextInput::make('tax')->numeric()->prefix('GHS')->required(),
                    TextInput::make('service_charge')->numeric()->prefix('GHS')->required(),
                    TextInput::make('total')->numeric()->prefix('GHS')->required(),
                    Textarea::make('notes')->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }
}
