<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class KitchenStockMovement extends Model
{
    public const TYPE_OPENING_STOCK = 'opening_stock';

    public const TYPE_RECEIPT = 'receipt';

    public const TYPE_CONSUMPTION = 'consumption';

    public const TYPE_WASTAGE = 'wastage';

    public const TYPE_ADJUSTMENT_IN = 'adjustment_in';

    public const TYPE_ADJUSTMENT_OUT = 'adjustment_out';

    public const TYPE_REVERSAL = 'reversal';

    public const DIRECTION_IN = 'in';

    public const DIRECTION_OUT = 'out';

    protected $fillable = ['ingredient_id', 'type', 'direction', 'quantity', 'balance_before', 'balance_after', 'unit_cost', 'total_cost', 'reference_number', 'supplier_name', 'reference_type', 'reference_id', 'performed_by', 'occurred_at', 'notes'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3', 'balance_before' => 'decimal:3', 'balance_after' => 'decimal:3', 'unit_cost' => 'decimal:2', 'total_cost' => 'decimal:2', 'occurred_at' => 'datetime'];
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
