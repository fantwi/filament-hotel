<x-auth-layout>
    <div class="mb-7 text-center sm:mb-8">
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600">Guest portal</p>
        <h1 class="mt-2 text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">Verify your sign-in</h1>
        <p class="mt-2 text-sm leading-6 text-gray-600">Enter the six-digit code from your authenticator app, or use a recovery code if your device is unavailable.</p>
    </div>

    <x-auth-session-status class="mb-5" :status="session('status')" />

    <form method="POST" action="{{ route('two-factor.verify') }}" class="space-y-5">
        @csrf
        <div>
            <x-input-label for="code" :value="__('Authenticator or recovery code')" class="text-sm font-medium text-gray-800" />
            <x-text-input id="code" class="mt-2 block min-h-12 w-full rounded-xl border-gray-300 px-4 text-center text-base tracking-[0.3em] shadow-sm focus:border-blue-500 focus:ring-blue-500" type="text" name="code" required autofocus autocomplete="one-time-code" />
            <p class="mt-2 text-xs leading-5 text-gray-500">Authenticator codes are six digits. Recovery codes can be entered with or without their hyphen.</p>
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <button type="submit" class="flex min-h-12 w-full items-center justify-center rounded-xl bg-blue-600 px-4 py-3 text-base font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            {{ __('Verify and continue') }}
        </button>
    </form>

    <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-4 text-center">
        <p class="text-sm text-gray-600">Do not have your authenticator device?</p>
        <form method="POST" action="{{ route('two-factor.email.request') }}" class="mt-3">
            @csrf
            <button type="submit" class="min-h-11 rounded-xl border border-blue-600 px-4 py-2 text-sm font-semibold text-blue-700 transition hover:bg-blue-50">Email me a verification code</button>
        </form>
        @error('email_code')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
        @if (session('status') === 'two-factor-email-sent')
            <p class="mt-2 text-sm text-emerald-700">A verification code was sent to your account email.</p>
        @endif
    </div>

    <div class="mt-7 border-t border-gray-200 pt-6 text-center">
        <a href="{{ route('login') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">Cancel and return to sign in</a>
    </div>
</x-auth-layout>
