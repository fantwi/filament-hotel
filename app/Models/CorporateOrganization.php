<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
