<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;

/**
 * Handles guest authenticator-app setup and login challenges.
 */
class TwoFactorAuthenticationController extends Controller
{
    /**
     * Begin a guest's authenticator-app setup without persisting an unconfirmed secret.
     */
    public function setup(Request $request): RedirectResponse
    {
        $user = $this->guestUser($request);

        if ($user->twoFactorEnabled()) {
            return redirect()->route('profile')->with('status', 'two-factor-already-enabled');
        }

        $codes = [];
        for ($index = 0; $index < 8; $index++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4)).'-'.bin2hex(random_bytes(4)));
        }

        $request->session()->put('two_factor_setup', [
            'secret' => (new Google2FA)->generateSecretKey(),
            'recovery_codes' => $codes,
            'started_at' => now()->timestamp,
        ]);

        return redirect()->route('profile')->with('status', 'two-factor-setup-started');
    }

    /**
     * Confirm the authenticator code and persist the encrypted secret and hashed recovery codes.
     */
    public function confirm(Request $request): RedirectResponse
    {
        $user = $this->guestUser($request);
        $setup = $request->session()->get('two_factor_setup');

        if (! is_array($setup) || blank($setup['secret'] ?? null) ||
            now()->timestamp - (int) ($setup['started_at'] ?? 0) > 600) {
            $request->session()->forget('two_factor_setup');

            throw ValidationException::withMessages([
                'code' => 'Your two-factor setup session expired. Start setup again.',
            ]);
        }

        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $google2fa = new Google2FA;
        if (! $google2fa->verifyKey($setup['secret'], $validated['code'], 1)) {
            throw ValidationException::withMessages([
                'code' => 'That authenticator code is invalid or has expired.',
            ]);
        }

        $user->forceFill([
            'two_factor_secret' => $setup['secret'],
            'two_factor_recovery_codes' => array_map(
                fn (string $code): string => Hash::make($this->normalizeRecoveryCode($code)),
                $setup['recovery_codes'] ?? [],
            ),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $request->session()->forget('two_factor_setup');
        $request->session()->flash('two_factor_recovery_codes', $setup['recovery_codes'] ?? []);

        return redirect()->route('profile')->with('status', 'two-factor-enabled');
    }

    /**
     * Disable two-factor authentication after verifying the guest's current password.
     */
    public function disable(Request $request): RedirectResponse
    {
        $user = $this->guestUser($request);

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'The password is incorrect.',
            ]);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $request->session()->forget(['two_factor_setup', 'two_factor_recovery_codes']);

        return redirect()->route('profile')->with('status', 'two-factor-disabled');
    }

    /**
     * Display the second-factor challenge for the pending guest login.
     */
    public function challenge(Request $request): View|RedirectResponse
    {
        $user = $this->pendingUser($request);

        if (! $user) {
            return redirect()->route('login')->with('message', 'Please sign in to continue.');
        }

        return view('auth.two-factor-challenge', ['user' => $user]);
    }

    /**
     * Verify a TOTP code or consume one recovery code and finish the guest login.
     */
    public function verify(Request $request): RedirectResponse
    {
        $user = $this->pendingUser($request);

        if (! $user) {
            return redirect()->route('login')->with('message', 'Your sign-in session expired. Please try again.');
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:64'],
        ]);

        $input = trim($validated['code']);
        $google2fa = new Google2FA;
        $totpCode = preg_replace('/\D+/', '', $input) ?? '';
        $valid = strlen($totpCode) === 6
            && $google2fa->verifyKey($user->two_factor_secret, $totpCode, 1);
        $recoveryIndex = null;

        if (! $valid) {
            $normalizedRecoveryCode = $this->normalizeRecoveryCode($input);
            foreach ($user->two_factor_recovery_codes ?? [] as $index => $hashedCode) {
                if (Hash::check($normalizedRecoveryCode, $hashedCode)) {
                    $valid = true;
                    $recoveryIndex = $index;
                    break;
                }
            }
        }

        if (! $valid) {
            throw ValidationException::withMessages([
                'code' => 'The code is invalid. Try again or use a recovery code.',
            ]);
        }

        if ($recoveryIndex !== null) {
            $recoveryCodes = $user->two_factor_recovery_codes ?? [];
            unset($recoveryCodes[$recoveryIndex]);
            $user->forceFill(['two_factor_recovery_codes' => array_values($recoveryCodes)])->save();
        }

        $remember = (bool) $request->session()->pull('two_factor.remember', false);
        $request->session()->forget('two_factor.pending_user_id');
        Auth::login($user, $remember);
        $request->session()->regenerate();

        ActivityLog::create([
            'user_id' => $user->id,
            'model' => User::class,
            'model_id' => $user->id,
            'action' => 'Logged in with two-factor authentication',
        ]);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function guestUser(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User && $user->isGuest(), 403);

        return $user;
    }

    private function pendingUser(Request $request): ?User
    {
        $userId = $request->session()->get('two_factor.pending_user_id');
        $user = $userId ? User::find($userId) : null;

        if (! $user instanceof User || ! $user->twoFactorEnabled()) {
            $request->session()->forget(['two_factor.pending_user_id', 'two_factor.remember']);

            return null;
        }

        return $user;
    }

    private function normalizeRecoveryCode(string $code): string
    {
        return strtoupper((string) preg_replace('/[^A-Z0-9]/i', '', $code));
    }
}
