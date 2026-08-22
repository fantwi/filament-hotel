<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Provides reusable model behavior for has publication state.
 */
trait HasPublicationState
{
    /**
     * Defines the boot has publication state relationship or domain behavior for this model.
     */
    protected static function bootHasPublicationState(): void
    {
        static::creating(function ($model): void {
            if (! $model->created_by && auth()->check()) {
                $model->created_by = auth()->id();
            }
        });
    }

    /**
     * Applies the published query scope.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * Applies the visible to query scope.
     */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user) {
            return $query->published();
        }

        return $query->where(function (Builder $query) use ($user): void {
            $query->where('is_published', true)
                ->orWhere('created_by', $user->id);
        });
    }
}
