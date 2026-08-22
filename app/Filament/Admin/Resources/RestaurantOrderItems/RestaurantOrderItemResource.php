<?php

namespace App\Filament\Admin\Resources\RestaurantOrderItems;

use App\Filament\Admin\Resources\RestaurantOrderItems\Pages\CreateRestaurantOrderItem;
use App\Filament\Admin\Resources\RestaurantOrderItems\Pages\EditRestaurantOrderItem;
use App\Filament\Admin\Resources\RestaurantOrderItems\Pages\ListRestaurantOrderItems;
use App\Filament\Admin\Resources\RestaurantOrderItems\Schemas\RestaurantOrderItemForm;
use App\Filament\Admin\Resources\RestaurantOrderItems\Tables\RestaurantOrderItemsTable;
use App\Models\RestaurantOrderItem;
use BackedEnum;
use App\Filament\Admin\Resources\SecureResource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

/**
 * Configures Filament administration for restaurant order item resource.
 */
class RestaurantOrderItemResource extends SecureResource
{
    protected static ?string $model = RestaurantOrderItem::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-list-bullet';

    protected static string|\UnitEnum|null $navigationGroup = 'Restaurant';

    protected static ?string $navigationLabel = 'Food Order Items';

    protected static ?int $navigationSort = 70;

    /**
     * Determines whether the current user may view these records.
     */
    public static function canViewAny(): bool
    {
        return auth()->user()?->can('manage kitchen orders') ?? false;
    }


    /**
     * Configures the form schema and input behavior.
     */
    public static function form(Schema $schema): Schema
    {
        return RestaurantOrderItemForm::configure($schema);
    }

    /**
     * Configures the table data source, columns, and actions.
     */
    public static function table(Table $table): Table
    {
        return RestaurantOrderItemsTable::configure($table);
    }

    /**
     * Builds and returns relations.
     */
    public static function getRelations(): array
    {
        return [];
    }

    /**
     * Builds and returns pages.
     */
    public static function getPages(): array
    {
        return [
            'index' => ListRestaurantOrderItems::route('/'),
            'create' => CreateRestaurantOrderItem::route('/create'),
            'edit' => EditRestaurantOrderItem::route('/{record}/edit'),
        ];
    }
}
