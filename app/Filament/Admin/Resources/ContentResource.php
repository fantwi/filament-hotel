<?php

namespace App\Filament\Admin\Resources;

use Illuminate\Database\Eloquent\Model;

/**
 * Public-facing content may only be managed by designated leadership roles.
 */
abstract class ContentResource extends SecureResource
{
    /**
     * Determines whether the current user may manage published content.
     */
    protected static function canManageContent(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager']) ?? false;
    }

    /**
     * Determines whether the current user may view these records.
     */
    public static function canViewAny(): bool
    {
        return static::canManageContent();
    }

    /**
     * Determines whether the current user may create records.
     */
    public static function canCreate(): bool
    {
        return static::canManageContent();
    }

    /**
     * Determines whether the current user may edit the supplied record.
     */
    public static function canEdit(Model $record): bool
    {
        return static::canManageContent();
    }

    /**
     * Determines whether the current user may delete the supplied record.
     */
    public static function canDelete(Model $record): bool
    {
        return static::canManageContent();
    }

    /**
     * Determines whether the current user may delete these records.
     */
    public static function canDeleteAny(): bool
    {
        return static::canManageContent();
    }
}
