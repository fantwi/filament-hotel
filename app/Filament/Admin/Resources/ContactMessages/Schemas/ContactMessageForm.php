<?php

namespace App\Filament\Admin\Resources\ContactMessages\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

/**
 * Configures Filament administration for contact message form.
 */
class ContactMessageForm
{
    /**
     * Configures configure for the Filament administration interface.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
                TextInput::make('name')->disabled(),
                TextInput::make('email')->disabled(),
                TextInput::make('phone_number')->disabled(),
                TextInput::make('subject')->disabled(),
                Textarea::make('message')->disabled(),
                Select::make('status')
                    ->options([
                        'new' => 'New',
                        'resolved' => 'Resolved',
                    ]),
            ]);
    }
}
