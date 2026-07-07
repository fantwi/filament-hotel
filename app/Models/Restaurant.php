<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    protected $fillable = [

        'name',

        'description',

        'hero_image',

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

    public function tables()
    {
        return $this->hasMany(
            RestaurantTable::class
        );
    }
}
