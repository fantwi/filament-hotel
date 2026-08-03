<?php

namespace App\Filament\Admin\Resources;

use Illuminate\Database\Eloquent\Model;

/**
 * Public-facing content may only be managed by designated leadership roles.
 */
abstract class ContentResource extends SecureResource
{
    protected static function canManageContent(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager']) ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::canManageContent();
    }

    public static function canCreate(): bool
    {
        return static::canManageContent();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canManageContent();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canManageContent();
    }

    public static function canDeleteAny(): bool
    {
        return static::canManageContent();
    }
}
