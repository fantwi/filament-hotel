<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use App\Enums\StaffAccountStatus;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

/**
 * Configures Filament administration for user form.
 */
class UserForm
{
    /**
     * Configures configure for the Filament administration interface.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('first_name')->required(),
            TextInput::make('last_name')->required(),
            TextInput::make('email')->email()->required(),
            TextInput::make('phone_number')->required(),
            Select::make('department')
                ->options(fn (): array => auth()->user()?->hasRole('super_admin')
                    ? User::DEPARTMENTS
                    : collect(User::DEPARTMENTS)->except(['super_admin', 'admin'])->all())
                ->required(),
            Select::make('corporate_organization_id')
                ->label('Corporate Account')
                ->relationship(
                    'corporateOrganization',
                    'name',
                    fn (Builder $query): Builder => $query->where('is_credit_enabled', true),
                )
                ->searchable()
                ->preload()
                ->nullable()
                ->placeholder('Personal / pay immediately')
                ->helperText('Select an enabled organisation to allow deferred payment. Clear this field to unlink the guest.'),
            TextInput::make('password')
                ->password()
                ->required(fn (string $operation): bool => $operation === 'create')
                ->dehydrated(fn ($state): bool => filled($state))
                ->dehydrateStateUsing(fn ($state) => bcrypt($state))
                ->label(fn (string $operation): string => $operation === 'edit' ? 'New Password' : 'Password')
                ->helperText('Leave blank to keep the current password.'),
            Select::make('status')
                ->label('Staff Status')
                ->options(StaffAccountStatus::options())
                ->default(StaffAccountStatus::Active->value)
                ->required(),
        ]);
    }
}
