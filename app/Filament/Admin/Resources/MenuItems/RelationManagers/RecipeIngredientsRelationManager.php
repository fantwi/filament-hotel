<?php

namespace App\Filament\Admin\Resources\MenuItems\RelationManagers;

use App\Models\RecipeIngredient;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class RecipeIngredientsRelationManager extends RelationManager
{
    protected static string $relationship = 'recipeIngredients';

    protected static ?string $title = 'Recipe Ingredients';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()?->can('manage menu item recipes') ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('ingredient_id')->label('Ingredient')->relationship('ingredient', 'name')->searchable()->preload()->required(),
            TextInput::make('quantity_per_item')->label('Quantity Per Menu Item')->numeric()->minValue(0.001)->step(0.001)->required(),
            Textarea::make('notes')->rows(3)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('ingredient.name')->label('Ingredient')->searchable()->sortable(),
                TextColumn::make('quantity_per_item')->label('Required')->formatStateUsing(fn (mixed $state, RecipeIngredient $record): string => number_format((float) $state, 3).' '.($record->ingredient?->unit ?? '')),
                TextColumn::make('ingredient.current_stock')->label('Current Stock')->formatStateUsing(fn (mixed $state, RecipeIngredient $record): string => number_format((float) $state, 3).' '.($record->ingredient?->unit ?? '')),
                TextColumn::make('notes')->placeholder('No notes')->wrap(),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
