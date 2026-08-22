<?php

namespace App\Models;

use App\Models\Concerns\HasPublicationState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents menu item and its persisted business behavior.
 */
class MenuItem extends Model
{
    use HasPublicationState;

    protected $fillable = [
        'menu_category_id',
        'name',
        'slug',
        'description',
        'price',
        'image',
        'is_available',
        'is_featured',
        'is_published',
        'created_by',
        'tracks_kitchen_production',
        'production_unit',
        'production_usage_per_sale',
        'inventory_consumption_mode',
        'low_stock_threshold',
        'preparation_time',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_available' => 'boolean',
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
        'tracks_kitchen_production' => 'boolean',
        'production_usage_per_sale' => 'decimal:3',
        'inventory_consumption_mode' => 'string',
        'low_stock_threshold' => 'decimal:3',
    ];

    /**
     * Defines the category relationship or domain behavior for this model.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(MenuCategory::class, 'menu_category_id');
    }

    /**
     * Defines the order items relationship or domain behavior for this model.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(RestaurantOrderItem::class);
    }

    /**
     * Defines the kitchen productions relationship or domain behavior for this model.
     */
    public function kitchenProductions(): HasMany
    {
        return $this->hasMany(KitchenProduction::class);
    }

    /**
     * Defines the recipe ingredients relationship or domain behavior for this model.
     */
    public function recipeIngredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class);
    }

    /**
     * Defines the ingredients relationship or domain behavior for this model.
     */
    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class, 'recipe_ingredients')
            ->withPivot(['quantity_per_item', 'notes'])
            ->withTimestamps();
    }

    /**
     * Determines whether the current user may start kitchen preparation.
     */
    public function canPrepare(int $quantity = 1): bool
    {
        if ($this->inventory_consumption_mode !== 'per_order') {
            return true;
        }

        $this->loadMissing('recipeIngredients.ingredient');

        foreach ($this->recipeIngredients as $recipe) {
            $ingredient = $recipe->ingredient;

            if (! $ingredient?->is_active || (float) $ingredient->current_stock < (float) $recipe->quantity_per_item * $quantity) {
                return false;
            }
        }

        return true;
    }
}
