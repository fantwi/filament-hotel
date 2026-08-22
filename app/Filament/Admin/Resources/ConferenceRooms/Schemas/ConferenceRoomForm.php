<?php

namespace App\Filament\Admin\Resources\ConferenceRooms\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

/**
 * Configures Filament administration for conference room form.
 */
class ConferenceRoomForm
{
    /**
     * Configures configure for the Filament administration interface.
     */
    public static function configure(Schema $schema): Schema
    {
        // return $schema
        //     ->components([
        //         //
        //     ]);

        return $schema
            ->components([

                TextInput::make('name')
                    ->required(),

                Textarea::make('description'),

                TextInput::make('capacity')
                    ->numeric()
                    ->required(),

                TextInput::make('price_per_hour')
                    ->numeric()
                    ->prefix('GHS'),

                CheckboxList::make('facilities')
                    ->relationship('facilities', 'name', modifyQueryUsing: fn (Builder $query) => $query->visibleTo(auth()->user()))
                    ->columns(2)
                    ->searchable(),

                FileUpload::make('image')
                    ->label('Image')
                    ->image()
                    ->disk('public')
                    ->directory('conference-rooms')
                    ->visibility('public')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(5120)
                    ->rules(['dimensions:max_width=4096,max_height=4096']),

                FileUpload::make('gallery')
                    ->label('Conference Room Gallery')
                    ->image()
                    ->multiple()
                    ->reorderable()
                    ->appendFiles()
                    ->maxFiles(12)
                    ->disk('public')
                    ->directory('conference-rooms/gallery')
                    ->visibility('public')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(5120)
                    ->rules(['dimensions:max_width=4096,max_height=4096'])
                    ->columnSpanFull(),

                Toggle::make('is_available'),

                Toggle::make('is_published')
                    ->label('Published for guests')
                    ->helperText('Only you can see this conference room in Filament until it is published.')
                    ->onIcon('heroicon-m-eye')
                    ->offIcon('heroicon-m-eye-slash')
                    ->default(false),

            ]);
    }
}
