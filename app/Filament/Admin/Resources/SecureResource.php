<?php

namespace App\Filament\Admin\Resources;

use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

/**
 * Filament's non-strict fallback permits actions without a model policy.
 * Every admin resource must therefore opt into each capability explicitly.
 */
abstract class SecureResource extends Resource
{
    /**
     * Determines whether the current user may access this resource.
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();

        if ($user?->isStaff() && ! $user->hasActiveStaffAccount()) {
            return false;
        }

        return parent::canAccess();
    }

    /**
     * Determines whether the current user may view these records.
     */
    public static function canViewAny(): bool
    {
        return false;
    }

    /**
     * Determines whether the current user may view this feature.
     */
    public static function canView(Model $record): bool
    {
        return static::canViewAny();
    }

    /**
     * Determines whether the current user may create records.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Determines whether the current user may edit the supplied record.
     */
    public static function canEdit(Model $record): bool
    {
        return false;
    }

    /**
     * Determines whether the current user may delete the supplied record.
     */
    public static function canDelete(Model $record): bool
    {
        return false;
    }

    /**
     * Determines whether the current user may delete these records.
     */
    public static function canDeleteAny(): bool
    {
        return false;
    }

    /**
     * Determines whether the current user may permanently delete the supplied record.
     */
    public static function canForceDelete(Model $record): bool
    {
        return false;
    }

    /**
     * Determines whether the current user may permanently delete these records.
     */
    public static function canForceDeleteAny(): bool
    {
        return false;
    }

    /**
     * Determines whether the current user may restore the supplied record.
     */
    public static function canRestore(Model $record): bool
    {
        return false;
    }

    /**
     * Determines whether the current user may restore these records.
     */
    public static function canRestoreAny(): bool
    {
        return false;
    }

    /**
     * Determines whether the current user may duplicate the supplied record.
     */
    public static function canReplicate(Model $record): bool
    {
        return false;
    }
}
