<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KitchenProductionIngredient extends Model
{
    protected $fillable = ['kitchen_production_id', 'ingredient_id', 'quantity_used', 'unit', 'notes'];

    protected $casts = ['quantity_used' => 'decimal:3'];

    public function production(): BelongsTo
    {
        return $this->belongsTo(KitchenProduction::class, 'kitchen_production_id');
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }
}
