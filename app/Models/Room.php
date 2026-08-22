<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Represents room and its persisted business behavior.
 */
class Room extends Model
{
    //
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'room_type_id',
        'room_number',
        'status',
    ];

    /*
        |--------------------------------------------------------------------------
        | Relationships
        |--------------------------------------------------------------------------
    */
    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }

    /**
     * Defines the bookings relationship or domain behavior for this model.
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Builds and returns activitylog options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status'])     // log only status changes
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Room {$eventName}");
    }
}
