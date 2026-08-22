<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents activity log and its persisted business behavior.
 */
class ActivityLog extends Model
{
    //
    protected $fillable = [
        'user_id',
        'action',
        'model',
        'record_id',
        'old_values',
        'new_values',
        'ip_address',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * Defines the user relationship or domain behavior for this model.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
