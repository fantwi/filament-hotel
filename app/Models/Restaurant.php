<?php

namespace App\Models;

use App\Models\Concerns\HasPublicationState;
use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    use HasPublicationState;

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
        'is_published',
        'created_by',

    ];

    protected $casts = [
        'gallery' => 'array',
        'is_published' => 'boolean',
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

    public function facilities()
    {
        return $this->belongsToMany(Facility::class)->withTimestamps();
    }
    // End Relationships
}
