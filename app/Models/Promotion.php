<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $fillable = ['name', 'code', 'discount_type', 'discount_value', 'minimum_spend', 'starts_at', 'ends_at', 'is_active'];

    protected $casts = ['discount_value' => 'decimal:2', 'minimum_spend' => 'decimal:2', 'starts_at' => 'date', 'ends_at' => 'date', 'is_active' => 'boolean'];

    public function scopeApplicable(Builder $query, float $subtotal): Builder
    {
        return $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhereDate('starts_at', '<=', today()))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhereDate('ends_at', '>=', today()))
            ->where(fn (Builder $q) => $q->whereNull('minimum_spend')->orWhere('minimum_spend', '<=', $subtotal));
    }
}
