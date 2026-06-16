<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\RoomType;
use App\Models\Booking;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

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

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status'])     // log only status changes
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Room {$eventName}");
    }
}
