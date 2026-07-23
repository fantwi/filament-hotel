<?php

namespace App\Filament\Admin\Resources\MenuCategories;

use App\Filament\Admin\Resources\MenuCategories\Pages\CreateMenuCategory;
use App\Filament\Admin\Resources\MenuCategories\Pages\EditMenuCategory;
use App\Filament\Admin\Resources\MenuCategories\Pages\ListMenuCategories;
use App\Filament\Admin\Resources\MenuCategories\Schemas\MenuCategoryForm;
use App\Filament\Admin\Resources\MenuCategories\Tables\MenuCategoriesTable;
use App\Models\MenuCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class MenuCategoryResource extends Resource
{
    protected static ?string $model = MenuCategory::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string|\UnitEnum|null $navigationGroup = 'Restaurant';

    protected static ?string $navigationLabel = 'Menu Categories';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return MenuCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MenuCategoriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMenuCategories::route('/'),
            'create' => CreateMenuCategory::route('/create'),
            'edit' => EditMenuCategory::route('/{record}/edit'),
        ];
    }
}
