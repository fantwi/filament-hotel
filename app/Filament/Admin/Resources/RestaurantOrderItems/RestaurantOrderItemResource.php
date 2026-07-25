<?php

namespace App\Filament\Admin\Resources\RestaurantOrderItems;

use App\Filament\Admin\Resources\RestaurantOrderItems\Pages\CreateRestaurantOrderItem;
use App\Filament\Admin\Resources\RestaurantOrderItems\Pages\EditRestaurantOrderItem;
use App\Filament\Admin\Resources\RestaurantOrderItems\Pages\ListRestaurantOrderItems;
use App\Filament\Admin\Resources\RestaurantOrderItems\Schemas\RestaurantOrderItemForm;
use App\Filament\Admin\Resources\RestaurantOrderItems\Tables\RestaurantOrderItemsTable;
use App\Models\RestaurantOrderItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class RestaurantOrderItemResource extends Resource
{
    protected static ?string $model = RestaurantOrderItem::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-list-bullet';

    protected static string|\UnitEnum|null $navigationGroup = 'Restaurant';

    protected static ?string $navigationLabel = 'Food Order Items';

    protected static ?int $navigationSort = 70;

    public static function form(Schema $schema): Schema
    {
        return RestaurantOrderItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RestaurantOrderItemsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRestaurantOrderItems::route('/'),
            'create' => CreateRestaurantOrderItem::route('/create'),
            'edit' => EditRestaurantOrderItem::route('/{record}/edit'),
        ];
    }
}
