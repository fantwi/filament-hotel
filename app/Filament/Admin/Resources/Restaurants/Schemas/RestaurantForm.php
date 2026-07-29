<?php

namespace App\Filament\Admin\Resources\Restaurants\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

// use Filament\Forms\Components\Section;

class RestaurantForm
{
    public static function configure(Schema $schema): Schema
    {

        return $schema

            ->components([

                Section::make('Restaurant Information')

                    ->schema([

                        TextInput::make('name')
                            ->required(),

                        Textarea::make('description')
                            ->rows(5)
                            ->required(),

                        FileUpload::make('hero_image')
                            ->label('Hero Image')
                            ->image()
                            ->disk('public')
                            ->directory('restaurants')
                            ->visibility('public'),

                        FileUpload::make('gallery')
                            ->label('Gallery Images')
                            ->image()
                            ->multiple()
                            ->disk('public')
                            ->directory('restaurants/gallery')
                            ->visibility('public')
                            ->imageEditor()
                            ->reorderable()
                            ->appendFiles()
                            ->maxFiles(12)
                            ->maxSize(5120)
                            ->acceptedFileTypes([
                                'image/jpeg',
                                'image/png',
                                'image/webp',
                            ])
                            ->helperText('Upload up to 12 JPG, PNG, or WebP images. Maximum size: 5 MB each.')
                            ->columnSpanFull(),

                        TextInput::make('phone'),

                        TextInput::make('email')
                            ->email(),

                        TextInput::make('address'),

                        TimePicker::make('opening_time')
                            ->seconds(false),

                        TimePicker::make('closing_time')
                            ->seconds(false),

                        TextInput::make('capacity')
                            ->numeric(),

                        TextInput::make('dress_code'),

                        TextInput::make('cuisine'),

                        Toggle::make('is_open'),

                        Toggle::make('is_published')
                            ->label('Published for guests')
                            ->helperText('Only you can see this restaurant in Filament until it is published.')
                            ->onIcon('heroicon-m-eye')
                            ->offIcon('heroicon-m-eye-slash')
                            ->default(false),

                        CheckboxList::make('facilities')
                            ->relationship('facilities', 'name', modifyQueryUsing: fn (Builder $query) => $query->visibleTo(auth()->user()))
                            ->columns(2)
                            ->searchable()
                            ->columnSpanFull(),

                    ])

                    ->columns(2),

            ]);

    }
}
