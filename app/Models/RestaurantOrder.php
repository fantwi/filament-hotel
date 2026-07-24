<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class RestaurantOrder extends Model
{
    protected $fillable = [
        'guest_id',
        'restaurant_reservation_id',
        'order_number',
        'customer_email',
        'transaction_reference',
        'payment_method',
        'paid_at',
        'subtotal',
        'tax',
        'service_charge',
        'total',
        'status',
        'payment_status',
        'notes',
        'kitchen_notes',
        'confirmed_at',
        'preparing_at',
        'ready_at',
        'served_at',
        'cancelled_at',
        'prepared_by',
        'served_by',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'service_charge' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'preparing_at' => 'datetime',
        'ready_at' => 'datetime',
        'served_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(RestaurantOrderItem::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(RestaurantReservation::class, 'restaurant_reservation_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function servedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'served_by');
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('payment_status', 'completed');
    }

    public function scopeKitchenQueue(Builder $query): Builder
    {
        return $query->paid()->whereIn('status', ['confirmed', 'preparing', 'ready']);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['served', 'cancelled']);
    }
}
