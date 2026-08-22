<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Represents restaurant table and its persisted business behavior.
 */
class RestaurantTable extends Model
{
    protected $fillable = [

        'restaurant_id',

        'table_number',

        'capacity',

        'location',

        'status',

        'description',

        'reservation_fee',

        'image',

        'qr_code',
        'qr_token',
        'qr_ordering_enabled',

    ];

    protected $casts = [
        'capacity' => 'integer',
        'reservation_fee' => 'decimal:2',
        'qr_ordering_enabled' => 'boolean',
    ];

    /**
     * Registers lifecycle hooks and model behavior.
     */
    protected static function booted(): void
    {
        static::creating(function (RestaurantTable $table): void {
            if (blank($table->qr_token)) {
                $table->qr_token = Str::random(48);
            }
        });
    }

    /**
     * Defines the restaurant relationship or domain behavior for this model.
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Defines the reservations relationship or domain behavior for this model.
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(
            RestaurantReservation::class
        );
    }

    /**
     * Defines the orders relationship or domain behavior for this model.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(RestaurantOrder::class, 'restaurant_table_id');
    }
    // end relationships
}
