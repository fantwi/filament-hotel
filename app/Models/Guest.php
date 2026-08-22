<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Represents guest and its persisted business behavior.
 */
class Guest extends Model
{
    //
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'phone_number',
        'email',
        'id_number',
        'profile_photo',
    ];

    // Relationships
    /**
     * Defines the bookings relationship or domain behavior for this model.
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Defines the user relationship or domain behavior for this model.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Defines the restaurant reservations relationship or domain behavior for this model.
     */
    public function restaurantReservations()
    {
        return $this->hasMany(
            RestaurantReservation::class
        );
    }

    /**
     * Defines the restaurant orders relationship or domain behavior for this model.
     */
    public function restaurantOrders(): HasMany
    {
        return $this->hasMany(RestaurantOrder::class);
    }
    // End Relationships

    // Methods
    /**
     * Exposes the computed  name attribute.
     */
    public function getNameAttribute()
    {
        return $this->first_name.' '.$this->last_name;
    }

    /**
     * Exposes the computed  full name attribute.
     */
    public function getFullNameAttribute()
    {
        return $this->first_name.' '.$this->last_name;
    }

    /**
     * Builds and returns activitylog options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Guest {$eventName}");
    }
}
