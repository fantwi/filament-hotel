<?php

namespace App\Filament\Admin\Resources\RoomTypes\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
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
                Section::make('Room details')
                    ->description('Set the guest-facing room information, nightly rate, and occupancy limit.')
                    ->schema([
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
                        TextInput::make('capacity')
                            ->required()
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->step(1),
                        Textarea::make('description')
                            ->default(null)
                            ->required(fn (Get $get): bool => (bool) $get('is_published'))
                            ->columnSpanFull(),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ]),
                Section::make('Guest media')
                    ->description('Upload the cover image and gallery photographs shown to guests.')
                    ->schema([
                        FileUpload::make('image')
                            ->label('Cover image')
                            ->required(fn (Get $get): bool => (bool) $get('is_published'))
                            ->directory('room-types')
                            ->image()
                            ->disk('public')
                            ->visibility('public')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(5120)
                            ->rules(['dimensions:max_width=4096,max_height=4096'])
                            ->imageEditor(),
                        FileUpload::make('gallery')
                            ->label('Room gallery')
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
                            ->helperText('Add photos guests can browse from the room listing.'),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ]),
                Section::make('Facilities & publishing')
                    ->description('Assign amenities and control whether guests can book this room type.')
                    ->schema([
                        CheckboxList::make('facilities')
                            ->relationship('facilities', 'name', modifyQueryUsing: fn (Builder $query) => $query->visibleTo(auth()->user()))
                            ->columns(2)
                            ->searchable(),
                        Toggle::make('is_published')
                            ->label('Published for guests')
                            ->helperText('Publishing requires a name, positive nightly price, guest capacity, description, and cover image. Unpublishing preserves booking and payment history.')
                            ->onIcon('heroicon-m-eye')
                            ->offIcon('heroicon-m-eye-slash')
                            ->live()
                            ->default(false),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
