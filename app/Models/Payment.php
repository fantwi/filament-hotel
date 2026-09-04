<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Represents payment and its persisted business behavior.
 */
class Payment extends Model
{
    //
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'booking_id',
        'conference_booking_id',
        'restaurant_reservation_id',
        'restaurant_order_id',
        'guest_id',
        'amount',
        'method',
        'payment_status',
        // 'payment_date',
        'transaction_reference',
    ];

    protected $casts = [
        'refunded_at' => 'datetime',
    ];

    /**
     * Defines the booking relationship or domain behavior for this model.
     */
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Defines the guest relationship or domain behavior for this model.
     */
    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    /**
     * Defines the conference booking relationship or domain behavior for this model.
     */
    public function conferenceBooking()
    {
        return $this->belongsTo(
            ConferenceBooking::class
        );
    }

    /**
     * Defines the restaurant reservation relationship or domain behavior for this model.
     */
    public function restaurantReservation()
    {
        return $this->belongsTo(
            RestaurantReservation::class
        );
    }

    /**
     * Defines the restaurant order relationship or domain behavior for this model.
     */
    public function restaurantOrder()
    {
        return $this->belongsTo(RestaurantOrder::class);
    }

    /**
     * Defines the transaction label relationship or domain behavior for this model.
     */
    public function transactionLabel(): string
    {
        return match (true) {
            $this->booking_id !== null => 'Hotel booking #'.$this->booking_id,
            $this->conference_booking_id !== null => 'Conference booking #'.$this->conference_booking_id,
            $this->restaurant_reservation_id !== null => 'Table reservation #'.$this->restaurant_reservation_id,
            $this->restaurant_order_id !== null => 'Food order #'.$this->restaurant_order_id,
            default => 'Unlinked payment',
        };
    }

    /**
     * Defines the transaction guest relationship or domain behavior for this model.
     */
    public function transactionGuest(): ?Guest
    {
        return $this->guest
            ?? $this->booking?->guest
            ?? $this->conferenceBooking?->guest
            ?? $this->restaurantReservation?->guest
            ?? $this->restaurantOrder?->guest;
    }

    /**
     * Defines the transaction guest name relationship or domain behavior for this model.
     */
    public function transactionGuestName(): string
    {
        $guest = $this->transactionGuest();

        return $guest ? trim($guest->full_name) : 'Guest not recorded';
    }

    /**
     * Registers lifecycle hooks and model behavior.
     */
    protected static function booted()
    {
        static::saving(function (Payment $payment): void {
            if (
                $payment->isDirty('payment_status')
                && in_array($payment->payment_status, ['refunded', 'refund'], true)
                && $payment->refunded_at === null
            ) {
                $payment->refunded_at = now();
            }
        });

        static::created(function ($payment) {
            $booking = $payment->booking
                ?? $payment->conferenceBooking
                ?? $payment->restaurantReservation
                ?? $payment->restaurantOrder;

            if (! $booking) {
                return;
            }

            activity()
                ->causedBy(auth()->user() ?? $booking->guest?->user)
                ->performedOn($payment)
                ->log('Payment created');
        });

        static::updated(function ($payment) {
            $booking = $payment->booking
                ?? $payment->conferenceBooking
                ?? $payment->restaurantReservation
                ?? $payment->restaurantOrder;

            activity()
                ->causedBy(
                    auth()->user() ?? $booking?->guest?->user
                )
                ->performedOn(
                    $payment
                )
                ->log(
                    'Payment updated'
                );
        });
    }

    /**
     * Builds and returns activitylog options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Payment {$eventName}");
    }
}
