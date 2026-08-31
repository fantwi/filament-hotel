<?php

namespace App\Filament\Admin\Resources\RestaurantTables;

use App\Filament\Admin\Resources\ContentResource;
use App\Filament\Admin\Resources\RestaurantTables\Pages\CreateRestaurantTable;
use App\Filament\Admin\Resources\RestaurantTables\Pages\EditRestaurantTable;
use App\Filament\Admin\Resources\RestaurantTables\Pages\ListRestaurantTables;
use App\Filament\Admin\Resources\RestaurantTables\Schemas\RestaurantTableForm;
use App\Filament\Admin\Resources\RestaurantTables\Tables\RestaurantTablesTable;
use App\Models\RestaurantTable;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Configures Filament administration for restaurant table resource.
 */
class RestaurantTableResource extends ContentResource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Restaurant Sales';

    protected static ?int $navigationSort = 40;

    protected static ?string $model = RestaurantTable::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static ?string $recordTitleAttribute = 'table_number';

    /**
     * Configures the form schema and input behavior.
     */
    public static function form(Schema $schema): Schema
    {
        return RestaurantTableForm::configure($schema);
    }

    /**
     * Configures the table data source, columns, and actions.
     */
    public static function table(Table $table): Table
    {
        return RestaurantTablesTable::configure($table);
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
            'index' => ListRestaurantTables::route('/'),
            'create' => CreateRestaurantTable::route('/create'),
            'edit' => EditRestaurantTable::route('/{record}/edit'),
        ];
    }
}
