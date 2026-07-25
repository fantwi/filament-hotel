<?php

namespace App\Models;

use App\Models\Concerns\HasPublicationState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'low_stock_threshold' => 'decimal:3',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(MenuCategory::class, 'menu_category_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(RestaurantOrderItem::class);
    }

    public function kitchenProductions(): HasMany { return $this->hasMany(KitchenProduction::class); }
}
