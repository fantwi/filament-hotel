<?php

namespace App\Filament\Admin\Resources\RoomTypes\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

/**
 * Configures Filament administration for room type form.
 */
class RoomTypeForm
{
    /**
     * Configures configure for the Filament administration interface.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->trim()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('price_per_night')
                    ->required()
                    ->numeric()
                    ->prefix('GHS')
                    ->minValue(0.01)
                    ->step(0.01)
                    ->multipleOf(0.01),
                FileUpload::make('image')
                    ->directory('room-types')
                    ->image()
                    ->disk('public')
                    ->visibility('public')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(5120)
                    ->rules(['dimensions:max_width=4096,max_height=4096'])
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
                    ->maxFiles(12)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(5120)
                    ->rules(['dimensions:max_width=4096,max_height=4096'])
                    ->helperText('Add photos guests can browse from the room listing.')
                    ->columnSpanFull(),
                TextInput::make('capacity')
                    ->required()
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->step(1),
                Textarea::make('description')
                    ->default(null)
                    ->columnSpanFull(),
                Toggle::make('is_published')
                    ->label('Published for guests')
                    ->helperText('Unpublish room types that are no longer offered. This preserves their room, booking, and payment history.')
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
