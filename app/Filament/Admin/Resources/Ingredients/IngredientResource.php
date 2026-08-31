<?php

namespace App\Filament\Admin\Resources\Ingredients;

use App\Filament\Admin\Resources\Ingredients\Pages\CreateIngredient;
use App\Filament\Admin\Resources\Ingredients\Pages\EditIngredient;
use App\Filament\Admin\Resources\Ingredients\Pages\ListIngredients;
use App\Filament\Admin\Resources\Ingredients\Schemas\IngredientForm;
use App\Filament\Admin\Resources\Ingredients\Tables\IngredientsTable;
use App\Filament\Admin\Resources\SecureResource;
use App\Models\Ingredient;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Configures Filament administration for ingredient resource.
 */
class IngredientResource extends SecureResource
{
    protected static ?string $model = Ingredient::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static string|\UnitEnum|null $navigationGroup = 'Kitchen & Inventory';

    protected static ?string $navigationLabel = 'Kitchen Stock';

    protected static ?int $navigationSort = 10;

    /**
     * Determines whether the current user may view these records.
     */
    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view kitchen stock') ?? false;
    }

    /**
     * Determines whether the current user may create records.
     */
    public static function canCreate(): bool
    {
        return auth()->user()?->can('manage kitchen stock') ?? false;
    }

    /**
     * Determines whether the current user may edit the supplied record.
     */
    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('manage kitchen stock') ?? false;
    }

    /**
     * Determines whether the current user may delete the supplied record.
     */
    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('manage kitchen stock') ?? false;
    }

    /**
     * Configures the form schema and input behavior.
     */
    public static function form(Schema $schema): Schema
    {
        return IngredientForm::configure($schema);
    }

    /**
     * Configures the table data source, columns, and actions.
     */
    public static function table(Table $table): Table
    {
        return IngredientsTable::configure($table);
    }

    /**
     * Builds and returns pages.
     */
    public static function getPages(): array
    {
        return [
            'index' => ListIngredients::route('/'),
            'create' => CreateIngredient::route('/create'),
            'edit' => EditIngredient::route('/{record}/edit'),
        ];
    }
}
