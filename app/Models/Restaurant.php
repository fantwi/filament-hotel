<?php

namespace App\Models;

use App\Models\Concerns\HasPublicationState;
use Illuminate\Database\Eloquent\Model;

/**
 * Represents restaurant and its persisted business behavior.
 */
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
    /**
     * Defines the tables relationship or domain behavior for this model.
     */
    public function tables()
    {
        return $this->hasMany(
            RestaurantTable::class
        );
    }

    /**
     * Defines the reservations relationship or domain behavior for this model.
     */
    public function reservations()
    {
        return $this->hasMany(
            RestaurantReservation::class
        );
    }

    /**
     * Defines the facilities relationship or domain behavior for this model.
     */
    public function facilities()
    {
        return $this->belongsToMany(Facility::class)->withTimestamps();
    }
    // End Relationships
}
