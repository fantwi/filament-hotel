<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

/**
 * Represents kitchen production and its persisted business behavior.
 */
class KitchenProduction extends Model
{
    protected $fillable = ['menu_item_id', 'restaurant_id', 'batch_reference', 'production_date', 'quantity_produced', 'quantity_wasted', 'produced_by', 'notes', 'voided_at', 'voided_by', 'void_reason'];

    protected $casts = ['production_date' => 'date', 'quantity_produced' => 'decimal:3', 'quantity_wasted' => 'decimal:3', 'voided_at' => 'datetime'];

    /**
     * Registers lifecycle hooks and model behavior.
     */
    protected static function booted(): void
    {
        static::creating(function (self $production): void {
            $production->batch_reference ??= 'KP-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        });
    }

    /**
     * Defines the menu item relationship or domain behavior for this model.
     */
    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    /**
     * Identifies the restaurant whose ingredient inventory this batch uses.
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Defines the producer relationship or domain behavior for this model.
     */
    public function producer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'produced_by');
    }

    /**
     * Identifies the staff member who voided this inventory batch.
     */
    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    /**
     * Limits operational queries to production batches that remain posted.
     */
    public function scopeNotVoided(Builder $query): Builder
    {
        return $query->whereNull('voided_at');
    }

    /**
     * Defines the ingredients relationship or domain behavior for this model.
     */
    public function ingredients(): HasMany
    {
        return $this->hasMany(KitchenProductionIngredient::class);
    }

    /**
     * Returns immutable stock consumption and reversal entries for this batch.
     */
    public function stockMovements(): MorphMany
    {
        return $this->morphMany(KitchenStockMovement::class, 'reference')
            ->chaperone('reference')
            ->oldest('occurred_at')
            ->oldest('id');
    }
}
