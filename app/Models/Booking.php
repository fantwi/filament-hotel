<?php

namespace App\Models;

use App\Services\RoomAssignmentService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Booking extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'guest_id',
        'room_id',
        'check_in',
        'check_out',
        'total_price',
        'status',
        'invoice_number',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
    ];

    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeOverlapping(Builder $query, $checkIn, $checkOut): Builder
    {
        return $query
            ->whereDate('check_in', '<', $checkOut)
            ->whereDate('check_out', '>', $checkIn);
    }

    public function getTotalPaidAttribute()
    {
        return $this->payments()->sum('amount');
    }

    public function getBalanceAttribute()
    {
        return $this->total_price - $this->payments()->sum('amount');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Booking {$eventName}");
    }

    public function getPaymentStatusAttribute()
    {
        if ($this->balance <= 0) {
            return 'paid';
        }

        if ($this->total_paid > 0) {
            return 'partial';
        }

        return 'unpaid';
    }

    protected static function booted()
    {
        static::saving(function ($booking) {
            if (! $booking->check_in || ! $booking->check_out) {
                return;
            }

            $days = Carbon::parse($booking->check_in)
                ->diffInDays(Carbon::parse($booking->check_out));

            $room = Room::query()
                ->with('roomType')
                ->find($booking->room_id);

            if ($room?->roomType) {
                $booking->total_price = max($days, 1) * $room->roomType->price_per_night;
            }
        });

        static::creating(function ($booking) {
            if (! $booking->room_id && $booking->room_type_id) {
                $room = RoomAssignmentService::assignRoom(
                    $booking->room_type_id,
                    $booking->check_in,
                    $booking->check_out
                );

                if ($room) {
                    $booking->room_id = $room->id;
                }
            }
        });

        static::created(function ($booking) {
            static::syncRoomStatus($booking->room_id);
        });

        static::updated(function ($booking) {
            if ($booking->wasChanged('room_id')) {
                static::syncRoomStatus($booking->getOriginal('room_id'));
            }

            static::syncRoomStatus($booking->room_id);
        });
    }

    protected static function syncRoomStatus(?int $roomId): void
    {
        if (! $roomId) {
            return;
        }

        $room = Room::find($roomId);

        if (! $room) {
            return;
        }

        $hasCheckedInBooking = static::query()
            ->where('room_id', $roomId)
            ->where('status', 'checked_in')
            ->exists();

        if ($hasCheckedInBooking) {
            $room->update(['status' => 'occupied']);

            return;
        }

        if ($room->status !== 'maintenance') {
            $room->update(['status' => 'available']);
        }
    }
}
