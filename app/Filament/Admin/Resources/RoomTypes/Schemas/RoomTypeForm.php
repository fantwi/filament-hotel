<?php

namespace App\Filament\Admin\Resources\RoomTypes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Toggle;
use Illuminate\Database\Eloquent\Builder;

class RoomTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('price_per_night')
                    ->required()
                    ->numeric(),
                FileUpload::make('image')
                    ->directory('room-types')
                    ->image()
                    ->disk('public')
                    ->visibility('public')
                    // ->validationRules(['image', 'max:2048'])
                    ->imageEditor(),
                FileUpload::make('gallery')
                    ->label('Room Gallery')
                    ->directory('room-types/gallery')
                    ->disk('public')
                    ->visibility('public')
                    ->image()
                    ->multiple()
                    ->reorderable()
                    ->appendFiles()
                    ->helperText('Add photos guests can browse from the room listing.')
                    ->columnSpanFull(),
                TextInput::make('capacity')
                    ->required()
                    ->numeric(),
                Textarea::make('description')
                    ->default(null)
                    ->columnSpanFull(),
                Toggle::make('is_published')
                    ->label('Published for guests')
                    ->helperText('Only you can see this room type in Filament until it is published.')
                    ->onIcon('heroicon-m-eye')
                    ->offIcon('heroicon-m-eye-slash')
                    ->default(false),
                CheckboxList::make('facilities')
                    ->relationship('facilities', 'name', modifyQueryUsing: fn (Builder $query) => $query->visibleTo(auth()->user()))
                    ->columns(2)
                    ->searchable(),
            ]);
    }
}
