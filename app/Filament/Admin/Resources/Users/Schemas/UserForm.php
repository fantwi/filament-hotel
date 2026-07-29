<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('first_name')
                ->required(),

            TextInput::make('last_name')
                ->required(),

            TextInput::make('email')
                ->email()
                ->required(),

            TextInput::make('phone_number')
                ->required(),

            Select::make('department')
                ->options(User::getDepartments())
                ->required(),
            // ->dehydrated()
            // ->native(false),

            TextInput::make('password')
                ->password()
                ->required(fn (string $operation): bool => $operation === 'create')
                ->dehydrated(fn ($state): bool => filled($state))
                ->dehydrateStateUsing(fn ($state) => bcrypt($state))
                ->label(fn (string $operation): string => $operation === 'edit' ? 'New Password' : 'Password')
                ->helperText('Leave blank to keep the current password.'),

            // Select::make('roles')
            //     ->relationship('roles', 'name')
            //     ->multiple()
            //     ->preload()
            //     ->options(function () {
            //         $roles = \Spatie\Permission\Models\Role::pluck('name', 'name');

            //         if (! auth()->user()->hasRole('super_admin')) {
            //             $roles = $roles->except(['admin', 'super_admin']);
            //         }

            //         return $roles;
            //     }),

            // Select::make('roles')
            //     ->label('Role')
            //     // ->relationship('roles', 'name')
            //     ->required()
            //     ->multiple(false)
            //     ->preload()
            //     ->options([
            //         'super_admin' => 'Super Admin',
            //         'admin' => 'Admin',
            //         'manager' => 'Manager',
            //         'receptionist' => 'Receptionist',
            //         'housekeeping' => 'Housekeeping',
            //         'accountant' => 'Accountant',
            //     ])
            //     ->required(),

            Select::make('status')
                ->label('Staff Status')
                ->options([
                    'online' => 'Online',
                    'offline' => 'Offline',
                    'on_leave' => 'On Leave',
                    'suspended' => 'Suspended',
                ])
                ->default('online')
                ->required(),

            Select::make('shift')
                ->label('Work Shift')
                ->options([
                    'morning' => 'Morning Shift',
                    'evening' => 'Evening Shift',
                    'night' => 'Night Shift',
                    'off_duty' => 'Off Duty',
                ])
                ->default('off_duty')
                ->required(),
        ]);
    }
}
