<?php

namespace App\Filament\Admin\Resources\Restaurants;

use App\Filament\Admin\Resources\ContentResource;
use App\Filament\Admin\Resources\Restaurants\Pages\CreateRestaurant;
use App\Filament\Admin\Resources\Restaurants\Pages\EditRestaurant;
use App\Filament\Admin\Resources\Restaurants\Pages\ListRestaurants;
use App\Filament\Admin\Resources\Restaurants\Schemas\RestaurantForm;
use App\Filament\Admin\Resources\Restaurants\Tables\RestaurantsTable;
use App\Models\Restaurant;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Configures Filament administration for restaurant resource.
 */
class RestaurantResource extends ContentResource
{
    protected static ?string $model = Restaurant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\UnitEnum|null $navigationGroup = 'Hotel Configuration';

    protected static ?int $navigationSort = 20;

    /**
     * Configures the form schema and input behavior.
     */
    public static function form(Schema $schema): Schema
    {
        return RestaurantForm::configure($schema);
    }

    /**
     * Configures the table data source, columns, and actions.
     */
    public static function table(Table $table): Table
    {
        return RestaurantsTable::configure($table);
    }

    /**
     * Builds and returns eloquent query.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->visibleTo(auth()->user());
    }

    /**
     * Builds and returns relations.
     */
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * Builds and returns pages.
     */
    public static function getPages(): array
    {
        return [
            'index' => ListRestaurants::route('/'),
            'create' => CreateRestaurant::route('/create'),
            'edit' => EditRestaurant::route('/{record}/edit'),
        ];
    }
}
