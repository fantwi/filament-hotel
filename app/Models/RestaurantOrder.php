<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Represents restaurant order and its persisted business behavior.
 */
class RestaurantOrder extends Model
{
    protected $fillable = [
        'guest_id',
        'corporate_organization_id',
        'restaurant_reservation_id',
        'restaurant_table_id',
        'ordering_channel',
        'order_number',
        'customer_email',
        'transaction_reference',
        'payment_method',
        'paid_at',
        'subtotal',
        'discount',
        'promotion_code',
        'vat',
        'nhil',
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
        'stock_deducted_at',
        'stock_reversed_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'vat' => 'decimal:2',
        'nhil' => 'decimal:2',
        'tax' => 'decimal:2',
        'service_charge' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'preparing_at' => 'datetime',
        'ready_at' => 'datetime',
        'served_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'stock_deducted_at' => 'datetime',
        'stock_reversed_at' => 'datetime',
    ];

    /**
     * Returns the current restaurant cart items with their calculated line totals.
     */
    public function items(): HasMany
    {
        return $this->hasMany(RestaurantOrderItem::class);
    }

    /**
     * Defines the corporate organization relationship or domain behavior for this model.
     */
    public function corporateOrganization(): BelongsTo
    {
        return $this->belongsTo(CorporateOrganization::class);
    }

    /**
     * Defines the guest relationship or domain behavior for this model.
     */
    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    /**
     * Defines the reservation relationship or domain behavior for this model.
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(RestaurantReservation::class, 'restaurant_reservation_id');
    }

    /**
     * Configures the table data source, columns, and actions.
     */
    public function table(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class, 'restaurant_table_id');
    }

    /**
     * Defines the payments relationship or domain behavior for this model.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Defines the stock movements relationship or domain behavior for this model.
     */
    public function stockMovements(): MorphMany
    {
        return $this->morphMany(KitchenStockMovement::class, 'reference');
    }

    /**
     * Defines the prepared by relationship or domain behavior for this model.
     */
    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    /**
     * Defines the served by relationship or domain behavior for this model.
     */
    public function servedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'served_by');
    }

    /**
     * Applies the paid query scope.
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('payment_status', 'completed');
    }

    /**
     * Determines whether this record is kitchen eligible.
     */
    public function isKitchenEligible(): bool
    {
        return $this->payment_status === 'completed'
            || $this->payment_method === 'corporate_account';
    }

    /**
     * Applies the kitchen queue query scope.
     */
    public function scopeKitchenQueue(Builder $query): Builder
    {
        return $query
            ->where(function (Builder $paymentQuery): void {
                $paymentQuery
                    ->where('payment_status', 'completed')
                    ->orWhere('payment_method', 'corporate_account');
            })
            ->whereIn('status', ['confirmed', 'preparing', 'ready']);
    }

    /**
     * Applies the active query scope.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['served', 'cancelled']);
    }
}
