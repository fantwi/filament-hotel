<?php

namespace App\Filament\Admin\Resources\RestaurantReservations;

use App\Filament\Admin\Resources\RestaurantReservations\Pages\CreateRestaurantReservation;
use App\Filament\Admin\Resources\RestaurantReservations\Pages\EditRestaurantReservation;
use App\Filament\Admin\Resources\RestaurantReservations\Pages\ListRestaurantReservations;
use App\Filament\Admin\Resources\RestaurantReservations\Schemas\RestaurantReservationForm;
use App\Filament\Admin\Resources\RestaurantReservations\Tables\RestaurantReservationsTable;
use App\Models\RestaurantReservation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RestaurantReservationResource extends Resource
{
    protected static ?string $model = RestaurantReservation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return RestaurantReservationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RestaurantReservationsTable::configure($table);
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
            'index' => ListRestaurantReservations::route('/'),
            'create' => CreateRestaurantReservation::route('/create'),
            'edit' => EditRestaurantReservation::route('/{record}/edit'),
        ];
    }
}
