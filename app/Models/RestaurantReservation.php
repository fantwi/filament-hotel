<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Represents restaurant reservation and its persisted business behavior.
 */
class RestaurantReservation extends Model
{
    use LogsActivity;

    protected static $logName = 'Restaurant Reservation';

    protected $fillable = [

        'restaurant_id',

        'restaurant_table_id',

        'corporate_organization_id',
        'guest_id',

        'guest_name',

        'guest_email',

        'guest_phone',

        'reservation_date',

        'reservation_time',

        'number_of_guests',

        'reservation_fee',
        'subtotal',
        'discount',
        'vat',
        'nhil',
        'service_charge',
        'promotion_code',

        'transaction_reference',

        'access_token',

        'status',

        'payment_status',

        'hold_until',

        'hold_status',

        'special_requests',

        'duration_minutes',

        // 'notes',

    ];

    protected $casts = [

        'reservation_date' => 'date',
        'reservation_fee' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'vat' => 'decimal:2',
        'nhil' => 'decimal:2',
        'service_charge' => 'decimal:2',

        'reservation_time' => 'datetime:H:i',

        'hold_until' => 'datetime',

    ];

    /**
     * Builds and returns activitylog options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'restaurant_id',
                'restaurant_table_id',
                'guest_id',
                'status',
                'payment_status',
                'hold_status',
                'reservation_date',
                'reservation_time',
                'reservation_fee',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // relationships
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
     * Defines the restaurant relationship or domain behavior for this model.
     */
    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Configures the table data source, columns, and actions.
     */
    public function table()
    {
        return $this->belongsTo(
            RestaurantTable::class,
            'restaurant_table_id'
        );
    }

    /**
     * Defines the payments relationship or domain behavior for this model.
     */
    public function payments()
    {
        return $this->hasMany(
            Payment::class,
            'restaurant_reservation_id'
        );
    }
    // end relationships

    // scopes
    // public function scopePending($query)
    // {
    //     return $query->where('status', 'pending');
    // }

    // public function scopeConfirmed($query)
    // {
    //     return $query->where('status', 'confirmed');
    // }

    // public function scopeCheckedIn($query)
    // {
    //     return $query->where('status', 'checked_in');
    // }

    // public function scopeCompleted($query)
    // {
    //     return $query->where('status', 'completed');
    // }

    // public function scopeCancelled($query)
    // {
    //     return $query->where('status', 'cancelled');
    // }

    // public function scopeNoShow($query)
    // {
    //     return $query->where('status', 'no_show');
    // }

    // public function scopePendingPayment($query)
    // {
    //     return $query->where('payment_status', 'pending');
    // }

    // public function scopePartialPayment($query)
    // {
    //     return $query->where('payment_status', 'partial');
    // }

    // public function scopeCompletedPayment($query)
    // {
    //     return $query->where('payment_status', 'completed');
    // }

    // public function scopeCancelledPayment($query)
    // {
    //     return $query->where('payment_status', 'cancelled');
    // }

    // public function scopeRefunded($query)
    // {
    //     return $query->where('payment_status', 'refunded');
    // }

    // public function scopeHeld($query)
    // {
    //     return $query->where('hold_status', 'held');
    // }

    // public function scopeConfirmedHold($query)
    // {
    //     return $query->where('hold_status', 'confirmed');
    // }

    // public function scopeExpiredHold($query)
    // {
    //     return $query->where('hold_status', 'expired');
    // }
}
