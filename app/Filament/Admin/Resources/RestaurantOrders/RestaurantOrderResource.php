<?php

namespace App\Filament\Admin\Resources\RestaurantOrders;

use App\Filament\Admin\Resources\RestaurantOrders\Pages\CreateRestaurantOrder;
use App\Filament\Admin\Resources\RestaurantOrders\Pages\EditRestaurantOrder;
use App\Filament\Admin\Resources\RestaurantOrders\Pages\ListRestaurantOrders;
use App\Filament\Admin\Resources\RestaurantOrders\Schemas\RestaurantOrderForm;
use App\Filament\Admin\Resources\RestaurantOrders\Tables\RestaurantOrdersTable;
use App\Models\RestaurantOrder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class RestaurantOrderResource extends Resource
{
    protected static ?string $model = RestaurantOrder::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static string|\UnitEnum|null $navigationGroup = 'Restaurant';

    protected static ?string $navigationLabel = 'Food Orders';

    protected static ?int $navigationSort = 60;

    protected static ?string $recordTitleAttribute = 'order_number';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('manage kitchen orders') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return RestaurantOrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RestaurantOrdersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRestaurantOrders::route('/'),
            'create' => CreateRestaurantOrder::route('/create'),
            'edit' => EditRestaurantOrder::route('/{record}/edit'),
        ];
    }
}
