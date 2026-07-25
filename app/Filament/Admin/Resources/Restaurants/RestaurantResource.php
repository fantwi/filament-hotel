<?php

namespace App\Filament\Admin\Resources\Restaurants;

use App\Filament\Admin\Resources\Restaurants\Pages\CreateRestaurant;
use App\Filament\Admin\Resources\Restaurants\Pages\EditRestaurant;
use App\Filament\Admin\Resources\Restaurants\Pages\ListRestaurants;
use App\Filament\Admin\Resources\Restaurants\Schemas\RestaurantForm;
use App\Filament\Admin\Resources\Restaurants\Tables\RestaurantsTable;
use App\Models\Restaurant;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RestaurantResource extends Resource
{
    protected static ?string $model = Restaurant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return RestaurantForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RestaurantsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->visibleTo(auth()->user());
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRestaurants::route('/'),
            'create' => CreateRestaurant::route('/create'),
            'edit' => EditRestaurant::route('/{record}/edit'),
        ];
    }
}
