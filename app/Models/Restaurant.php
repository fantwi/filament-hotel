<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\RestaurantTable;

class Restaurant extends Model
{
    protected $fillable = [

        'name',

        'description',

        'hero_image',

        'gallery',

        'phone',

        'email',

        'address',

        'opening_time',

        'closing_time',

        'capacity',

        'dress_code',

        'cuisine',

        'facebook',

        'instagram',

        'x',

        'is_open',

    ];

    protected $casts = [
        'gallery' => 'array',
    ];

    // Relationships
    public function tables()
    {
        return $this->hasMany(
            RestaurantTable::class
        );
    }

    public function reservations()
    {
        return $this->hasMany(
            RestaurantReservation::class
        );
    }
    // End Relationships
}
