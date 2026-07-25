<?php

namespace App\Filament\Admin\Resources\MenuCategories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MenuCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Menu Category')
                ->schema([
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
                        ->rows(3)
                        ->columnSpanFull(),
                    TextInput::make('icon')
                        ->helperText('Optional icon name or emoji.'),
                    TextInput::make('sort_order')
                        ->numeric()
                        ->default(0)
                        ->required(),
                    Toggle::make('is_active')
                        ->default(true)
                        ->required(),
                    Toggle::make('is_published')
                        ->label('Published for guests')
                        ->helperText('Only you can see this category in Filament until it is published.')
                        ->onIcon('heroicon-m-eye')
                        ->offIcon('heroicon-m-eye-slash')
                        ->default(false),
                ])
                ->columns(2),
        ]);
    }
}
