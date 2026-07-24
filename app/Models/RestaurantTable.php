<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class RestaurantTable extends Model
{
    protected $fillable = [

        'restaurant_id',

        'table_number',

        'capacity',

        'location',

        'status',

        'description',

        'qr_code',
        'qr_token',
        'qr_ordering_enabled',

    ];

    protected $casts = [
        'capacity' => 'integer',
        'qr_ordering_enabled' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (RestaurantTable $table): void {
            if (blank($table->qr_token)) {
                $table->qr_token = Str::random(48);
            }
        });
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(
            RestaurantReservation::class
        );
    }

    public function orders(): HasMany
    {
        return $this->hasMany(RestaurantOrder::class, 'restaurant_table_id');
    }
    // end relationships
}
