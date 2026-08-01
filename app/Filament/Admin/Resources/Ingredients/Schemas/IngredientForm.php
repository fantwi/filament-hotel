<?php

namespace App\Filament\Admin\Resources\Ingredients\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class IngredientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Ingredient Information')
                ->description('Current stock is updated only through the audited stock actions.')
                ->schema([
                    Select::make('restaurant_id')->relationship('restaurant', 'name')->searchable()->preload()->required(),
                    TextInput::make('name')->required()->maxLength(255),
                    TextInput::make('sku')->label('SKU')->maxLength(255)->unique(ignoreRecord: true),
                    TextInput::make('category')->placeholder('Meat, Produce, Beverage')->maxLength(255),
                    Select::make('unit')->options(['kg' => 'Kilogram', 'g' => 'Gram', 'litre' => 'Litre', 'ml' => 'Millilitre', 'piece' => 'Piece', 'bottle' => 'Bottle', 'pack' => 'Pack', 'tray' => 'Tray', 'bag' => 'Bag'])->searchable()->required(),
                    TextInput::make('reorder_level')->numeric()->minValue(0)->step(0.001)->default(0)->required(),
                    TextInput::make('unit_cost')->label('Unit Cost')->numeric()->prefix('GHS')->minValue(0)->step(0.01)->default(0)->required(),
                    Toggle::make('is_active')->default(true),
                ])->columns(2),
        ]);
    }
}
