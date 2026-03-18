<?php

namespace App\Filament\Admin\Resources\Users;

use App\Filament\Admin\Resources\Users\Pages\CreateUser;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\Admin\Resources\Users\Schemas\UserForm;
use App\Filament\Admin\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'admin']);
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();

        if ($record->hasRole('admin') && !$user->hasRole('super_admin')) {
            return false;
        }

        if ($record->hasRole('super_admin') && !$user->hasRole('super_admin')) {
            return false;
        }

        return true;
    }

    public static function canDelete($record): bool
    {
        $user = auth()->user();

        if ($record->hasRole('admin') && !$user->hasRole('super_admin')) {
            return false;
        }

        if ($record->hasRole('super_admin') && !$user->hasRole('super_admin')) {
            return false;
        }

        if ($record->id === auth()->id()) {
            return false;
        }
    
        if ($record->hasRole('admin') && !auth()->user()->hasRole('super_admin')) {
            return false;
        }

        return true;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (!auth()->user()->hasRole('super_admin')) {
            $query->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'admin')
                ->orWhere('name','super_admin');
            });
        }

        return $query;
    }

}
