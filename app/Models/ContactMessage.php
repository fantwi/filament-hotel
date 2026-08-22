<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents contact message and its persisted business behavior.
 */
class ContactMessage extends Model
{
    //
    protected $fillable = [

        'name',

        'email',

        'phone_number',

        'subject',

        'message',

        'status',
    ];
}
