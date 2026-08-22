<?php

namespace App\Filament\Admin\Resources\ActivityLogs\Schemas;

use App\Filament\Admin\Resources\ActivityLogs\ActivityLogResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Schema;

/**
 * Configures Filament administration for activity log infolist.
 */
class ActivityLogInfolist
{
    /**
     * Configures configure for the Filament administration interface.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
                TextEntry::make('causer.name')
                    ->label('User'),

                TextEntry::make('description')
                    ->label('Action'),

                TextEntry::make('subject_type')
                    ->label('Model'),

                // TextEntry::make('properties.old')
                //     ->label('Old Values')
                //     ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT)),

                // TextEntry::make('properties.attributes')
                //     ->label('New Values')
                //     ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT)),

                // TextEntry::make('properties.ip_address')
                //     ->label('IP Address'),

                TextEntry::make('created_at')
                    ->label('Date & Time')
                    ->dateTime(),

                ViewEntry::make('changes')
                    ->label('Changes')
                    ->view('filament.activity-diff')
                    ->state(fn ($record) => ActivityLogResource::formatChanges($record))
                    ->columnSpanFull(),
            ]);
    }
}
