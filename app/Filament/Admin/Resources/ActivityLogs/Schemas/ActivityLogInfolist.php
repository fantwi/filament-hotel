<?php

namespace App\Filament\Admin\Resources\ActivityLogs\Schemas;

use Filament\Schemas\Schema;
use Filament\Infolists\Components\TextEntry;

class ActivityLogInfolist
{
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

                TextEntry::make('properties.old')
                    ->label('Old Values')
                    ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT)),

                TextEntry::make('properties.attributes')
                    ->label('New Values')
                    ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT)),

                TextEntry::make('properties.ip_address')
                    ->label('IP Address'),

                TextEntry::make('created_at')
                    ->label('Date & Time')
                    ->dateTime(),
            ]);
    }
}