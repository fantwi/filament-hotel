<?php

namespace App\Filament\Admin\Resources\RestaurantReservations;

use App\Filament\Admin\Resources\RestaurantReservations\Pages\CreateRestaurantReservation;
use App\Filament\Admin\Resources\RestaurantReservations\Pages\EditRestaurantReservation;
use App\Filament\Admin\Resources\RestaurantReservations\Pages\ListRestaurantReservations;
use App\Filament\Admin\Resources\RestaurantReservations\Schemas\RestaurantReservationForm;
use App\Filament\Admin\Resources\RestaurantReservations\Tables\RestaurantReservationsTable;
use App\Models\RestaurantReservation;
use BackedEnum;
use App\Filament\Admin\Resources\SecureResource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Configures Filament administration for restaurant reservation resource.
 */
class RestaurantReservationResource extends SecureResource
{
    protected static ?string $model = RestaurantReservation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Restaurant';

    protected static ?int $navigationSort = 30;

    /**
     * Configures may manage reservations for the Filament administration interface.
     */
    private static function mayManageReservations(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'receptionist']) ?? false;
    }

    /**
     * Determines whether the current user may view these records.
     */
    public static function canViewAny(): bool
    {
        return static::mayManageReservations();
    }

    /**
     * Determines whether the current user may create records.
     */
    public static function canCreate(): bool
    {
        return static::mayManageReservations();
    }

    /**
     * Determines whether the current user may edit the supplied record.
     */
    public static function canEdit($record): bool
    {
        return static::mayManageReservations();
    }


    /**
     * Configures the form schema and input behavior.
     */
    public static function form(Schema $schema): Schema
    {
        return RestaurantReservationForm::configure($schema);
    }

    /**
     * Configures the table data source, columns, and actions.
     */
    public static function table(Table $table): Table
    {
        return RestaurantReservationsTable::configure($table);
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
            'index' => ListRestaurantReservations::route('/'),
            'create' => CreateRestaurantReservation::route('/create'),
            'edit' => EditRestaurantReservation::route('/{record}/edit'),
        ];
    }
}
