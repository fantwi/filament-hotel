<x-guest-layout>
    <section class="px-4 py-10 sm:px-6 sm:py-14">
        <div class="mx-auto w-full max-w-md rounded-2xl bg-white p-5 text-center shadow-xl shadow-slate-200/70 ring-1 ring-slate-900/5 sm:p-8">
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600">Almost there</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">Verify your email</h1>
            <p class="mt-3 text-sm leading-6 text-gray-600">{{ __('We sent a verification link to your email address. Open it to activate your account. If you cannot find it, we can send another one.') }}</p>

    @if (session('status') == 'verification-link-sent')
        <div class="mt-5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

            <div class="mt-6 space-y-3 border-t border-gray-200 pt-6">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

                <button type="submit" class="flex min-h-12 w-full items-center justify-center rounded-xl bg-blue-600 px-4 py-3 text-base font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Resend verification email</button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

                <button type="submit" class="min-h-11 text-sm font-semibold text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Log out</button>
        </form>
            </div>
        </div>
    </section>
</x-guest-layout>
