<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Guest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone_number' => ['string', 'max:255'],
            'id_number' => ['string', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // $user = User::create([
        //     'first_name' => $request->first_name,
        //     'last_name' => $request->last_name,
        //     'email' => $request->email,
        //     'phone_number' => $request->phone_number,
        //     'id_number' => $request->id_number,
        //     'password' => Hash::make($request->password),
        // ]);

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'id_number' => $request->id_number,
            'password' => Hash::make($request->password),
            'department' => 'guest',
            'status' => User::STATUS_OFFLINE,
        ]);

        // ✅ CREATE GUEST AUTOMATICALLY
        // $guest = Guest::create([
        //     'user_id' => $user->id,
        //     'first_name' => $user->first_name,
        //     'last_name' => $user->last_name,
        //     'email' => $user->email,
        //     'phone_number' => $user->phone_number ?? null,
        //     'id_number' => $request->id_number ?? null,
        // ]);

        // Guest::create([
        //     'user_id' => $user->id,
        //     'first_name' => $request->first_name,
        //     'last_name' => $request->last_name,
        //     'email' => $request->email,
        //     'phone_number' => $request->phone_number ?? null,
        //     'id_number' => $request->id_number ?? null,
        // ]);

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'Registered account',
        ]);
        // if ($user->department === 'guest' && !$user->guest()->exists()) {
        //     Guest::create([
        //         'user_id' => $user->id,
        //         'first_name' => $request->first_name ?? 'Guest',
        //         'last_name' => $request->last_name ?? '',
        //         'email' => $request->email,
        //         'phone_number' => $request->phone_number ?? null,
        //         'id_number' => $request->id_number ?? null,
        //     ]);
        // };

        // if ($user->department === 'guest') {
        //     $user->assignRole('guest');
        // }
        // $user->assignRole('guest');

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
