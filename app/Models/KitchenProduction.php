<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class KitchenProduction extends Model
{
    protected $fillable = ['menu_item_id', 'batch_reference', 'production_date', 'quantity_produced', 'quantity_wasted', 'produced_by', 'notes'];
    protected $casts = ['production_date' => 'date', 'quantity_produced' => 'decimal:3', 'quantity_wasted' => 'decimal:3'];
    protected static function booted(): void { static::creating(function (self $production): void { $production->batch_reference ??= 'KP-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)); }); }
    public function menuItem(): BelongsTo { return $this->belongsTo(MenuItem::class); }
    public function producer(): BelongsTo { return $this->belongsTo(User::class, 'produced_by'); }
}
