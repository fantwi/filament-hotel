<?php

namespace App\Filament\Admin\Resources\Ingredients;

use App\Filament\Admin\Resources\Ingredients\Pages\CreateIngredient;
use App\Filament\Admin\Resources\Ingredients\Pages\EditIngredient;
use App\Filament\Admin\Resources\Ingredients\Pages\ListIngredients;
use App\Filament\Admin\Resources\Ingredients\Schemas\IngredientForm;
use App\Filament\Admin\Resources\Ingredients\Tables\IngredientsTable;
use App\Models\Ingredient;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class IngredientResource extends Resource
{
    protected static ?string $model = Ingredient::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static string|\UnitEnum|null $navigationGroup = 'Restaurant';

    protected static ?string $navigationLabel = 'Kitchen Stock';

    protected static ?int $navigationSort = 70;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view kitchen stock') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('manage kitchen stock') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('manage kitchen stock') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('manage kitchen stock') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return IngredientForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IngredientsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIngredients::route('/'),
            'create' => CreateIngredient::route('/create'),
            'edit' => EditIngredient::route('/{record}/edit'),
        ];
    }
}
