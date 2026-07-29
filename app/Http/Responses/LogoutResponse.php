<?php

namespace App\Http\Responses;

use Filament\Http\Responses\Auth\Contracts\LogoutResponse as Contract;

class LogoutResponse implements Contract
{
    public function toResponse($request)
    {
        return redirect('/'); // ✅ redirect to homepage
    }
}
