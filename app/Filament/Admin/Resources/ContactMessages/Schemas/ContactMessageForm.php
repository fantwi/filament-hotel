<?php

namespace App\Filament\Admin\Resources\ContactMessages\Schemas;

use Filament\Schemas\Schema;

class ContactMessageForm
{
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
                    ])
            ]);
    }
}
