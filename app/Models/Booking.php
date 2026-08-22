<?php

namespace App\Models;

use App\Services\RoomAssignmentService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Represents booking and its persisted business behavior.
 */
class Booking extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'guest_id',
        'room_id',

        'corporate_organization_id',
        'check_in',
        'check_out',

        'check_in_time',
        'check_out_time',

        'total_price',
        'payment_status',
        'transaction_reference',

        'hold_until',
        'hold_status',
        'invoice_number',
        'status',
    ];

    protected $casts = [
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'hold_until' => 'datetime',
    ];

    /**
     * Defines the corporate organization relationship or domain behavior for this model.
     */
    public function corporateOrganization()
    {
        return $this->belongsTo(CorporateOrganization::class);
    }

    /**
     * Defines the guest relationship or domain behavior for this model.
     */
    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }

    /**
     * Defines the user relationship or domain behavior for this model.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'guest_id');
    }

    /**
     * Defines the room relationship or domain behavior for this model.
     */
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Defines the payments relationship or domain behavior for this model.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Applies the overlapping query scope.
     */
    public function scopeOverlapping(Builder $query, $checkIn, $checkOut): Builder
    {
        return $query
            ->whereDate('check_in', '<', $checkOut)
            ->whereDate('check_out', '>', $checkIn);
    }

    /**
     * Exposes the computed  total paid attribute.
     */
    public function getTotalPaidAttribute()
    {
        return $this->payments()
            ->whereIn('payment_status', ['paid', 'completed'])
            ->sum('amount');
    }

    /**
     * Exposes the computed  balance attribute.
     */
    public function getBalanceAttribute()
    {
        return max(0, (float) $this->total_price - (float) $this->total_paid);
    }

    /**
     * Builds and returns activitylog options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Booking {$eventName}");
    }

    /**
     * Registers lifecycle hooks and model behavior.
     */
    protected static function booted()
    {
        static::saving(function ($booking) {
            if (! $booking->check_in || ! $booking->check_out || $booking->isDirty('total_price')) {
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

            activity()
                ->causedBy(auth()->user() ?? $booking->guest?->user)
                ->performedOn($booking)
                ->log('Booking created');
        });

        static::updated(function ($booking) {
            if ($booking->wasChanged('room_id')) {
                static::syncRoomStatus($booking->getOriginal('room_id'));

                activity()
                    ->causedBy(auth()->user() ?? $booking->guest?->user)
                    ->performedOn($booking)
                    ->log('Booking updated');
            }

            static::syncRoomStatus($booking->room_id);
        });
    }

    /**
     * Synchronizes  room status with the current state.
     */
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
