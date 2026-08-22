<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents conference booking and its persisted business behavior.
 */
class ConferenceBooking extends Model
{
    //
    protected $fillable = [

        'conference_room_id',

        'guest_id',
        'corporate_organization_id',

        'booking_date',

        'start_time',

        'end_time',

        'attendees',

        'subtotal',
        'discount',
        'vat',
        'nhil',
        'service_charge',
        'promotion_code',
        'total_price',

        'status',

        'payment_status',

        'transaction_reference',

        'hold_until',

    ];

    protected $casts = [

        'booking_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'vat' => 'decimal:2',
        'nhil' => 'decimal:2',
        'service_charge' => 'decimal:2',
        'total_price' => 'decimal:2',

        'hold_until' => 'datetime',

    ];

    /**
     * Defines the room relationship or domain behavior for this model.
     */
    public function room()
    {
        return $this->belongsTo(ConferenceRoom::class, 'conference_room_id');
    }

    /**
     * Defines the corporate organization relationship or domain behavior for this model.
     */
    public function corporateOrganization()
    {
        return $this->belongsTo(CorporateOrganization::class);
    }

    /**
     * Defines the payments relationship or domain behavior for this model.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Defines the guest relationship or domain behavior for this model.
     */
    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }
}
