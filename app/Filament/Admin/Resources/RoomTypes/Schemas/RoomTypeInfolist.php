<?php

namespace App\Filament\Admin\Resources\RoomTypes\Schemas;

use App\Models\RoomType;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Configures Filament administration for room type infolist.
 */
class RoomTypeInfolist
{
    /**
     * Configures configure for the Filament administration interface.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns([
                'default' => 1,
                'lg' => 3,
            ])
            ->components([
                Section::make('Room details')
                    ->description('Guest-facing accommodation details and physical room inventory.')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Room type')
                            ->weight('bold'),
                        TextEntry::make('price_per_night')
                            ->label('Nightly price')
                            ->money('GHS'),
                        TextEntry::make('capacity')
                            ->label('Guest capacity')
                            ->numeric()
                            ->suffix(' guests'),
                        TextEntry::make('rooms_count')
                            ->label('Physical rooms')
                            ->counts('rooms')
                            ->numeric()
                            ->suffix(' rooms'),
                        TextEntry::make('description')
                            ->placeholder('No description provided')
                            ->columnSpanFull(),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 2,
                    ]),

                Section::make('Cover image')
                    ->description('Primary image shown to guests.')
                    ->schema([
                        ImageEntry::make('image')
                            ->hiddenLabel()
                            ->disk('public')
                            ->visibility('public')
                            ->imageWidth('100%')
                            ->imageHeight('14rem')
                            ->alt(fn (RoomType $record): string => "{$record->name} cover image")
                            ->extraImgAttributes(['class' => 'rounded-xl object-cover'])
                            ->placeholder('No cover image uploaded'),
                    ]),

                Section::make('Gallery')
                    ->description('Additional guest-facing photographs for this room type.')
                    ->schema([
                        ImageEntry::make('gallery')
                            ->hiddenLabel()
                            ->disk('public')
                            ->visibility('public')
                            ->imageHeight(120)
                            ->square()
                            ->wrap()
                            ->alt(fn (RoomType $record): string => "{$record->name} gallery image")
                            ->placeholder('No gallery images uploaded'),
                    ])
                    ->columnSpanFull(),

                Section::make('Facilities')
                    ->description('Amenities assigned to this room type.')
                    ->schema([
                        TextEntry::make('facilities.name')
                            ->hiddenLabel()
                            ->badge()
                            ->placeholder('No facilities assigned'),
                    ]),

                Section::make('Publishing and audit')
                    ->description('Visibility, ownership, and record history.')
                    ->schema([
                        TextEntry::make('publication_status')
                            ->label('Publication status')
                            ->state(fn (RoomType $record): string => $record->is_published ? 'Published' : 'Draft')
                            ->badge()
                            ->color(fn (string $state): string => $state === 'Published' ? 'success' : 'gray')
                            ->icon(fn (string $state): string => $state === 'Published'
                                ? 'heroicon-o-eye'
                                : 'heroicon-o-eye-slash'),
                        TextEntry::make('creator.name')
                            ->label('Created by')
                            ->placeholder('System or unknown user'),
                        TextEntry::make('created_at')
                            ->label('Created at')
                            ->dateTime('M d, Y g:i A')
                            ->placeholder('Not recorded'),
                        TextEntry::make('updated_at')
                            ->label('Last updated')
                            ->dateTime('M d, Y g:i A')
                            ->placeholder('Not recorded'),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 2,
                    ]),
            ]);
    }
}
