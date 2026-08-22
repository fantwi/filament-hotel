<?php

namespace App\Http\Responses;

use Filament\Http\Responses\Auth\Contracts\LogoutResponse as Contract;

/**
 * Provides logout response behavior.
 */
class LogoutResponse implements Contract
{
    /**
     * Performs the to response operation.
     */
    public function toResponse($request)
    {
        return redirect('/'); // ✅ redirect to homepage
    }
}
