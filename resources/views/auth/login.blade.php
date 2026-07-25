<x-auth-layout>

    <div class="mb-7 text-center sm:mb-8">
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600">Guest portal</p>
        <h1 class="mt-2 text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">Welcome back</h1>
        <p class="mt-2 text-sm leading-6 text-gray-600">Sign in to manage your reservations and bookings.</p>
    </div>

    @if (session('message'))
        <div class="mb-5 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800" role="status">
            {{ session('message') }}
        </div>
    @endif

    <x-auth-session-status class="mb-5" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email address')" class="text-sm font-medium text-gray-800" />
            <x-text-input id="email" class="mt-2 block min-h-12 w-full rounded-xl border-gray-300 px-4 text-base shadow-sm focus:border-blue-500 focus:ring-blue-500" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <div class="flex items-center justify-between gap-3">
                <x-input-label for="password" :value="__('Password')" class="text-sm font-medium text-gray-800" />
                @if (Route::has('password.request'))
                    <a class="text-sm font-medium text-blue-600 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2" href="{{ route('password.request') }}">Forgot password?</a>
                @endif
            </div>
            <x-text-input id="password" class="mt-2 block min-h-12 w-full rounded-xl border-gray-300 px-4 text-base shadow-sm focus:border-blue-500 focus:ring-blue-500" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember_me" class="flex min-h-11 items-center gap-3 text-sm text-gray-700">
            <input id="remember_me" type="checkbox" class="h-5 w-5 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" name="remember">
            <span>{{ __('Keep me signed in') }}</span>
        </label>

        <button type="submit" class="flex min-h-12 w-full items-center justify-center rounded-xl bg-blue-600 px-4 py-3 text-base font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            {{ __('Sign in') }}
        </button>
    </form>

    @if (Route::has('register'))
        <div class="mt-7 border-t border-gray-200 pt-6 text-center">
            <p class="text-sm text-gray-600">New to {{ config('app.name') }}?</p>
            <a href="{{ route('register') }}" class="mt-3 flex min-h-12 w-full items-center justify-center rounded-xl border border-blue-600 px-4 py-3 text-base font-semibold text-blue-700 transition hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Create a guest account</a>
        </div>
    @endif
</x-auth-layout>
