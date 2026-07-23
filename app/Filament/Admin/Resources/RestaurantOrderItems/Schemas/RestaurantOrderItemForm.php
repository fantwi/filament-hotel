<?php

namespace App\Filament\Admin\Resources\RestaurantOrderItems\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RestaurantOrderItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Order Item')
                ->schema([
                    Select::make('restaurant_order_id')->relationship('order', 'order_number')->searchable()->preload()->required(),
                    Select::make('menu_item_id')->relationship('menuItem', 'name')->searchable()->preload()->required(),
                    TextInput::make('quantity')->numeric()->minValue(1)->required(),
                    TextInput::make('unit_price')->numeric()->prefix('GHS')->required(),
                    TextInput::make('total_price')->numeric()->prefix('GHS')->required(),
                ])
                ->columns(2),
        ]);
    }
}
