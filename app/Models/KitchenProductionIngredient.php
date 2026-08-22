<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents kitchen production ingredient and its persisted business behavior.
 */
class KitchenProductionIngredient extends Model
{
    protected $fillable = ['kitchen_production_id', 'ingredient_id', 'quantity_used', 'unit', 'notes'];

    protected $casts = ['quantity_used' => 'decimal:3'];

    /**
     * Defines the production relationship or domain behavior for this model.
     */
    public function production(): BelongsTo
    {
        return $this->belongsTo(KitchenProduction::class, 'kitchen_production_id');
    }

    /**
     * Defines the ingredient relationship or domain behavior for this model.
     */
    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }
}
