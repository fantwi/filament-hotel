<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CorporateOrganization extends Model
{
    protected $fillable = [
        'name', 'contact_name', 'email', 'phone', 'credit_limit',
        'payment_terms_days', 'is_credit_enabled',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'is_credit_enabled' => 'boolean',
    ];
}
