<?php

namespace App\Filament\Admin\Resources\MenuCategories;

use App\Filament\Admin\Resources\MenuCategories\Pages\CreateMenuCategory;
use App\Filament\Admin\Resources\MenuCategories\Pages\EditMenuCategory;
use App\Filament\Admin\Resources\MenuCategories\Pages\ListMenuCategories;
use App\Filament\Admin\Resources\MenuCategories\Schemas\MenuCategoryForm;
use App\Filament\Admin\Resources\MenuCategories\Tables\MenuCategoriesTable;
use App\Models\MenuCategory;
use BackedEnum;
use App\Filament\Admin\Resources\ContentResource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Configures Filament administration for menu category resource.
 */
class MenuCategoryResource extends ContentResource
{
    protected static ?string $model = MenuCategory::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string|\UnitEnum|null $navigationGroup = 'Restaurant';

    protected static ?string $navigationLabel = 'Menu Categories';

    protected static ?int $navigationSort = 40;

    protected static ?string $recordTitleAttribute = 'name';

    /**
     * Configures the form schema and input behavior.
     */
    public static function form(Schema $schema): Schema
    {
        return MenuCategoryForm::configure($schema);
    }

    /**
     * Configures the table data source, columns, and actions.
     */
    public static function table(Table $table): Table
    {
        return MenuCategoriesTable::configure($table);
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
        return [];
    }

    /**
     * Builds and returns pages.
     */
    public static function getPages(): array
    {
        return [
            'index' => ListMenuCategories::route('/'),
            'create' => CreateMenuCategory::route('/create'),
            'edit' => EditMenuCategory::route('/{record}/edit'),
        ];
    }
}
