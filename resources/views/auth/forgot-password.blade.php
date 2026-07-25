<x-guest-layout>
    <section class="px-4 py-10 sm:px-6 sm:py-14">
        <div class="mx-auto w-full max-w-md rounded-2xl bg-white p-5 shadow-xl shadow-slate-200/70 ring-1 ring-slate-900/5 sm:p-8">
            <div class="mb-7 text-center">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600">Account recovery</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">Reset your password</h1>
                <p class="mt-2 text-sm leading-6 text-gray-600">Enter your email address and we’ll send you a secure reset link.</p>
            </div>

            <x-auth-session-status class="mb-5" :status="session('status')" />

            <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
                @csrf

                <div>
                    <x-input-label for="email" :value="__('Email address')" class="text-sm font-medium text-gray-800" />
                    <x-text-input id="email" class="mt-2 block min-h-12 w-full rounded-xl border-gray-300 px-4 text-base shadow-sm focus:border-blue-500 focus:ring-blue-500" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <button type="submit" class="flex min-h-12 w-full items-center justify-center rounded-xl bg-blue-600 px-4 py-3 text-base font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Email password reset link
                </button>
            </form>

            <div class="mt-6 border-t border-gray-200 pt-5 text-center">
                <a href="{{ route('login') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Return to sign in</a>
            </div>
        </div>
    </section>
</x-guest-layout>
