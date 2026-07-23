<?php

namespace App\Filament\Admin\Resources\MenuItems\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MenuItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Menu Item')
                ->schema([
                    Select::make('menu_category_id')
                        ->relationship('category', 'name')
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
                    TextInput::make('sort_order')
                        ->numeric()
                        ->default(0)
                        ->required(),
                ])
                ->columns(2),
        ]);
    }
}
