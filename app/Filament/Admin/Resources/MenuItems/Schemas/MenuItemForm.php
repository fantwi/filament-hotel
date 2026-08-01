<?php

namespace App\Filament\Admin\Resources\MenuItems\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class MenuItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Menu Item')
                ->schema([
                    Select::make('menu_category_id')
                        ->relationship('category', 'name', modifyQueryUsing: fn (Builder $query) => $query->visibleTo(auth()->user()))
                        ->searchable()
                        ->preload()
                        ->required(),
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, callable $set) => $set('slug', str($state)->slug())),
                    TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    Textarea::make('description')
                        ->rows(4)
                        ->columnSpanFull(),
                    TextInput::make('price')
                        ->numeric()
                        ->prefix('GHS')
                        ->required(),
                    TextInput::make('preparation_time')
                        ->numeric()
                        ->suffix('mins')
                        ->default(20)
                        ->required(),
                    FileUpload::make('image')
                        ->directory('menu-items')
                        ->disk('public')
                        ->visibility('public')
                        ->image(),
                    Toggle::make('is_available')->default(true),
                    Toggle::make('is_featured')->default(false),
                    Toggle::make('is_published')
                        ->label('Published for guests')
                        ->helperText('Only you can see this item in Filament until it is published.')
                        ->onIcon('heroicon-m-eye')
                        ->offIcon('heroicon-m-eye-slash')
                        ->default(false),
                    TextInput::make('sort_order')
                        ->numeric()
                        ->default(0)
                        ->required(),
                ])
                ->columns(2),
            Section::make('Kitchen Production Tracking')
                ->description('Configure how this menu item is measured against kitchen production.')
                ->schema([
                    Toggle::make('tracks_kitchen_production')->label('Track Kitchen Production')->default(false)->live(),
                    Select::make('production_unit')->options(['portion' => 'Portion', 'piece' => 'Piece', 'tray' => 'Tray', 'kilogram' => 'Kilogram', 'gram' => 'Gram', 'litre' => 'Litre', 'millilitre' => 'Millilitre', 'bottle' => 'Bottle'])->default('portion')->required()->visible(fn ($get): bool => (bool) $get('tracks_kitchen_production')),
                    TextInput::make('production_usage_per_sale')->label('Production Amount Per Sale')->numeric()->minValue(0.001)->step(0.001)->default(1)->required()->visible(fn ($get): bool => (bool) $get('tracks_kitchen_production')),
                    TextInput::make('low_stock_threshold')->label('Low-Stock Threshold')->numeric()->minValue(0)->step(0.001)->default(0)->required()->visible(fn ($get): bool => (bool) $get('tracks_kitchen_production')),
                    Select::make('inventory_consumption_mode')
                        ->label('Ingredient Stock Deduction')
                        ->options([
                            'per_order' => 'When Order Preparation Starts',
                            'production_batch' => 'When Production Batch Is Recorded',
                            'none' => 'Do Not Deduct Ingredients',
                        ])
                        ->default('per_order')
                        ->required()
                        ->helperText('Choose when raw ingredient stock should be reduced.'),
                ])->columns(3),
        ]);
    }
}
