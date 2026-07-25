<x-guest-layout>
    <section class="px-4 py-10 sm:px-6 sm:py-14">
        <div class="mx-auto w-full max-w-md rounded-2xl bg-white p-5 shadow-xl shadow-slate-200/70 ring-1 ring-slate-900/5 sm:p-8">
            <div class="mb-7 text-center">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600">Account recovery</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">Choose a new password</h1>
                <p class="mt-2 text-sm leading-6 text-gray-600">Create a strong password to restore access to your account.</p>
            </div>

            <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
                <div>
            <x-input-label for="email" :value="__('Email address')" class="text-sm font-medium text-gray-800" />
            <x-text-input id="email" class="mt-2 block min-h-12 w-full rounded-xl border-gray-300 px-4 text-base shadow-sm focus:border-blue-500 focus:ring-blue-500" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
                <div>
            <x-input-label for="password" :value="__('New password')" class="text-sm font-medium text-gray-800" />
            <x-text-input id="password" class="mt-2 block min-h-12 w-full rounded-xl border-gray-300 px-4 text-base shadow-sm focus:border-blue-500 focus:ring-blue-500" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
                <div>
            <x-input-label for="password_confirmation" :value="__('Confirm new password')" class="text-sm font-medium text-gray-800" />

            <x-text-input id="password_confirmation" class="mt-2 block min-h-12 w-full rounded-xl border-gray-300 px-4 text-base shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                type="password"
                                name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

                <button type="submit" class="flex min-h-12 w-full items-center justify-center rounded-xl bg-blue-600 px-4 py-3 text-base font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Reset password</button>
            </form>
        </div>
    </section>
</x-guest-layout>
