<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate(); // regenerate the session

        // return redirect()->intended(route('dashboard', absolute: false));

        ActivityLog::create([
            'user_id' => auth()->id(),
            'model' => User::class,
            'model_id' => auth()->id(),
            'action' => 'Logged in',
            //    'subject_id' => $booking->id,
        ]);

        $user = auth()->user(); // get the authenticated user

        if ($user->isStaff()) {
            return redirect('/admin'); // filament panel
        }

        if ($user->isGuest()) {
            return redirect('/dashboard'); // guest dashboard
        }

        return redirect('/');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        ActivityLog::create([
            'user_id' => auth()->id(),
            'model' => User::class,
            'model_id' => auth()->id(),
            'action' => 'Logged out',
            // 'subject_id' => $booking->id,
        ]);

        return redirect('/');
    }
}
