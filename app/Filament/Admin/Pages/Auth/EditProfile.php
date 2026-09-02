<?php

namespace App\Filament\Admin\Pages\Auth;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Provides the staff profile editor for the Filament admin panel.
 */
class EditProfile extends BaseEditProfile
{
    /**
     * Labels the profile page and its user-menu entry.
     */
    public static function getLabel(): string
    {
        return 'My profile';
    }

    /**
     * Configures editable personal details and read-only staff metadata.
     */
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Personal information')
                    ->description('Keep your contact details current for hotel operations and account notifications.')
                    ->schema([
                        TextInput::make('first_name')
                            ->label('First name')
                            ->required()
                            ->maxLength(255)
                            ->autofocus(),
                        TextInput::make('last_name')
                            ->label('Last name')
                            ->required()
                            ->maxLength(255),
                        $this->getEmailFormComponent(),
                        TextInput::make('phone_number')
                            ->label('Phone number')
                            ->tel()
                            ->maxLength(50),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ]),
                Section::make('Staff account')
                    ->description('Contact an administrator when your access assignment or employment status needs to change.')
                    ->schema([
                        Placeholder::make('department_display')
                            ->label('Department')
                            ->content(fn (): string => $this->getUser()->department_label),
                        Placeholder::make('role_display')
                            ->label('Role')
                            ->content(fn (): string => $this->getUser()->role_name),
                        Placeholder::make('status_display')
                            ->label('Status')
                            ->content(fn (): string => $this->getUser()->status?->label() ?? 'Not set'),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 3,
                    ]),
                Section::make('Password')
                    ->description('Leave the new password blank to keep your current password.')
                    ->schema([
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                        $this->getCurrentPasswordFormComponent(),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ]),
            ]);
    }
}
