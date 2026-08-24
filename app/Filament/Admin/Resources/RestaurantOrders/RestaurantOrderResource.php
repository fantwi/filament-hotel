<?php

namespace App\Filament\Admin\Resources\RestaurantOrders;

use App\Filament\Admin\Resources\RestaurantOrders\Pages\CreateRestaurantOrder;
use App\Filament\Admin\Resources\RestaurantOrders\Pages\EditRestaurantOrder;
use App\Filament\Admin\Resources\RestaurantOrders\Pages\ListRestaurantOrders;
use App\Filament\Admin\Resources\RestaurantOrders\Schemas\RestaurantOrderForm;
use App\Filament\Admin\Resources\RestaurantOrders\Tables\RestaurantOrdersTable;
use App\Filament\Admin\Resources\SecureResource;
use App\Models\RestaurantOrder;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

/**
 * Configures Filament administration for restaurant order resource.
 */
class RestaurantOrderResource extends SecureResource
{
    protected static ?string $model = RestaurantOrder::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static string|\UnitEnum|null $navigationGroup = 'Restaurant';

    protected static ?string $navigationLabel = 'Food Orders';

    protected static ?int $navigationSort = 60;

    protected static ?string $recordTitleAttribute = 'order_number';

    /**
     * Determines whether the current user may view these records.
     */
    public static function canViewAny(): bool
    {
        return auth()->user()?->can('manage kitchen orders') ?? false;
    }

    /**
     * Determines whether the current user may edit the supplied record.
     */
    public static function canEdit($record): bool
    {
        return auth()->user()?->can('manage kitchen orders') ?? false;
    }

    /**
     * Configures the form schema and input behavior.
     */
    public static function form(Schema $schema): Schema
    {
        return RestaurantOrderForm::configure($schema);
    }

    /**
     * Configures the table data source, columns, and actions.
     */
    public static function table(Table $table): Table
    {
        return RestaurantOrdersTable::configure($table);
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
            'index' => ListRestaurantOrders::route('/'),
            'create' => CreateRestaurantOrder::route('/create'),
            'edit' => EditRestaurantOrder::route('/{record}/edit'),
        ];
    }
}
