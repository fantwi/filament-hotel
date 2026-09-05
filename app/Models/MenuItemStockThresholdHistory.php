<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Records the threshold that became effective for a menu item at a point in time.
 */
class MenuItemStockThresholdHistory extends Model
{
    protected $fillable = [
        'menu_item_id',
        'threshold',
        'effective_from',
    ];

    protected $casts = [
        'threshold' => 'decimal:3',
        'effective_from' => 'datetime',
    ];

    /**
     * Defines the menu item whose threshold changed.
     */
    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }
}
