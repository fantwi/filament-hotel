<?php

namespace App\Filament\Admin\Resources\Users;

use App\Filament\Admin\Resources\SecureResource;
use App\Filament\Admin\Resources\Users\Pages\CreateUser;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\Admin\Resources\Users\Schemas\UserForm;
use App\Filament\Admin\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Configures Filament administration for user resource.
 */
class UserResource extends SecureResource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Access & Administration';

    protected static ?int $navigationSort = 10;

    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    /**
     * Controls whether this feature appears in the Filament navigation.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole([
            'super_admin',
            'admin',
            'manager',
        ]);
    }

    /**
     * Determines whether the current user may view these records.
     */
    public static function canViewAny(): bool
    {
        return auth()->user()->hasAnyRole([
            'super_admin',
            'admin',
            'manager',
        ]);
    }

    /**
     * Configures the form schema and input behavior.
     */
    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    /**
     * Configures the table data source, columns, and actions.
     */
    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    /**
     * Builds and returns relations.
     */
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * Builds and returns pages.
     */
    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    /**
     * Determines whether the current user may create records.
     */
    public static function canCreate(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'admin']);
    }

    /**
     * Determines whether the current user may edit the supplied record.
     */
    public static function canEdit($record): bool
    {
        $user = auth()->user();

        if ($user?->hasRole('manager')) {
            return $record->department === 'guest';
        }

        if (! $user?->hasAnyRole(['super_admin', 'admin'])) {
            return false;
        }

        if ($record->hasAnyRole(['admin', 'super_admin'])) {
            return $user->hasRole('super_admin');
        }

        return true;
    }

    /**
     * Determines whether the current user may delete the supplied record.
     */
    public static function canDelete($record): bool
    {
        $user = auth()->user();

        if ($record->hasRole('admin') && ! $user->hasRole('super_admin')) {
            return false;
        }

        if ($record->hasRole('super_admin') && ! $user->hasRole('super_admin')) {
            return false;
        }

        if ($record->id === auth()->id()) {
            return false;
        }

        if ($record->hasRole('admin') && ! auth()->user()->hasRole('super_admin')) {
            return false;
        }

        return true;
    }

    /**
     * Builds and returns eloquent query.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (! auth()->user()->hasRole('super_admin')) {
            $query->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'admin')
                    ->orWhere('name', 'super_admin');
            });
        }

        return $query;
    }
}
