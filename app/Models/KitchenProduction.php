<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Represents kitchen production and its persisted business behavior.
 */
class KitchenProduction extends Model
{
    protected $fillable = ['menu_item_id', 'batch_reference', 'production_date', 'quantity_produced', 'quantity_wasted', 'produced_by', 'notes'];

    protected $casts = ['production_date' => 'date', 'quantity_produced' => 'decimal:3', 'quantity_wasted' => 'decimal:3'];

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
     * Defines the producer relationship or domain behavior for this model.
     */
    public function producer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'produced_by');
    }

    /**
     * Defines the ingredients relationship or domain behavior for this model.
     */
    public function ingredients(): HasMany
    {
        return $this->hasMany(KitchenProductionIngredient::class);
    }
}
