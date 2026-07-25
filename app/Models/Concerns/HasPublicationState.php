<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait HasPublicationState
{
    protected static function bootHasPublicationState(): void
    {
        static::creating(function ($model): void {
            if (! $model->created_by && auth()->check()) {
                $model->created_by = auth()->id();
            }
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

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
