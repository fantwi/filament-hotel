<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\TwoFactorEmailCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_start_two_factor_setup_from_their_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('two-factor.setup'))
            ->assertRedirect('/profile')
            ->assertSessionHas('status', 'two-factor-setup-started');

        $this->get('/profile')
            ->assertOk()
            ->assertSee('Scan this QR code')
            ->assertSee('Manual setup key');
    }

    public function test_guest_must_confirm_an_authenticator_code_before_two_factor_is_enabled(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('two-factor.setup'));
        $secret = session('two_factor_setup.secret');
        $code = (new Google2FA)->getCurrentOtp($secret);

        $this->actingAs($user)
            ->post(route('two-factor.confirm'), ['code' => $code])
            ->assertRedirect('/profile')
            ->assertSessionHas('status', 'two-factor-enabled');

        $user->refresh();

        $this->assertTrue($user->twoFactorEnabled());
        $this->assertCount(8, session('two_factor_recovery_codes'));
    }

    public function test_guest_login_requires_a_totp_code_when_two_factor_is_enabled(): void
    {
        $user = $this->enableTwoFactor(User::factory()->create());
        $code = (new Google2FA)->getCurrentOtp($user->two_factor_secret);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.challenge'));

        $this->assertGuest();

        $this->post(route('two-factor.challenge'), ['code' => $code])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_an_invalid_two_factor_code_keeps_the_guest_logged_out(): void
    {
        $user = $this->enableTwoFactor(User::factory()->create());

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.challenge'));

        $this->post(route('two-factor.challenge'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_guest_can_request_an_email_code_for_a_pending_two_factor_login(): void
    {
        $user = $this->enableTwoFactor(User::factory()->create());
        Notification::fake();

        $this->startTwoFactorLogin($user);

        $this->post(route('two-factor.email.request'))
            ->assertRedirect(route('two-factor.challenge'))
            ->assertSessionHas('status', 'two-factor-email-sent');

        Notification::assertSentTo($user, TwoFactorEmailCode::class);
    }

    public function test_guest_can_finish_two_factor_login_with_the_emailed_code(): void
    {
        $user = $this->enableTwoFactor(User::factory()->create());
        Notification::fake();
        $this->startTwoFactorLogin($user);

        $this->post(route('two-factor.email.request'));
        $emailCode = null;
        Notification::assertSentTo($user, TwoFactorEmailCode::class, function (TwoFactorEmailCode $notification) use (&$emailCode): bool {
            $emailCode = $notification->code;

            return true;
        });

        $this->post(route('two-factor.verify'), ['code' => $emailCode])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_expired_email_codes_cannot_complete_two_factor_login(): void
    {
        $user = $this->enableTwoFactor(User::factory()->create());
        $this->startTwoFactorLogin($user);

        session()->put('two_factor.email_code', [
            'hash' => Hash::make('123456'),
            'expires_at' => now()->subMinute()->timestamp,
        ]);

        $this->post(route('two-factor.verify'), ['code' => '123456'])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_email_code_requests_are_throttled_for_a_pending_login(): void
    {
        $user = $this->enableTwoFactor(User::factory()->create());
        Notification::fake();
        $this->startTwoFactorLogin($user);

        $this->post(route('two-factor.email.request'));

        $this->post(route('two-factor.email.request'))
            ->assertSessionHasErrors('email_code');

        Notification::assertSentToTimes($user, TwoFactorEmailCode::class, 1);
    }

    public function test_unverified_guest_email_cannot_be_used_as_a_two_factor_fallback(): void
    {
        $user = $this->enableTwoFactor(User::factory()->unverified()->create());
        Notification::fake();
        $this->startTwoFactorLogin($user);

        $this->post(route('two-factor.email.request'))
            ->assertSessionHasErrors('email_code');

        Notification::assertNothingSent();
    }

    public function test_guest_can_use_a_recovery_code_once_when_the_authenticator_is_unavailable(): void
    {
        $recoveryCode = 'ABCD1234EFGH';
        $user = User::factory()->create();
        $user->forceFill([
            'two_factor_secret' => (new Google2FA)->generateSecretKey(),
            'two_factor_recovery_codes' => [Hash::make($recoveryCode)],
            'two_factor_confirmed_at' => now(),
        ])->save();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.challenge'));

        $this->post(route('two-factor.challenge'), ['code' => $recoveryCode])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
        $this->assertSame([], $user->fresh()->two_factor_recovery_codes);
    }

    public function test_guest_can_disable_two_factor_with_their_current_password(): void
    {
        $user = $this->enableTwoFactor(User::factory()->create());

        $this->actingAs($user)
            ->post(route('two-factor.disable'), ['current_password' => 'password'])
            ->assertRedirect('/profile')
            ->assertSessionHas('status', 'two-factor-disabled');

        $this->assertFalse($user->fresh()->twoFactorEnabled());
    }

    public function test_staff_accounts_cannot_enable_guest_two_factor_authentication(): void
    {
        $staff = User::factory()->create(['department' => 'kitchen_manager']);

        $this->actingAs($staff)
            ->post(route('two-factor.setup'))
            ->assertForbidden();
    }

    private function enableTwoFactor(User $user): User
    {
        $user->forceFill([
            'two_factor_secret' => (new Google2FA)->generateSecretKey(),
            'two_factor_recovery_codes' => [Hash::make('ABCD1234EFGH')],
            'two_factor_confirmed_at' => now(),
        ])->save();

        return $user->refresh();
    }

    private function startTwoFactorLogin(User $user): void
    {
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.challenge'));
    }
}
