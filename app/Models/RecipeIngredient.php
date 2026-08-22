<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents recipe ingredient and its persisted business behavior.
 */
class RecipeIngredient extends Model
{
    protected $fillable = ['menu_item_id', 'ingredient_id', 'quantity_per_item', 'notes'];

    /**
     * Defines the casts relationship or domain behavior for this model.
     */
    protected function casts(): array
    {
        return ['quantity_per_item' => 'decimal:3'];
    }

    /**
     * Defines the menu item relationship or domain behavior for this model.
     */
    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    /**
     * Defines the ingredient relationship or domain behavior for this model.
     */
    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }
}
