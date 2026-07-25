<x-guest-layout>
    <section class="px-4 py-10 sm:px-6 sm:py-16">
        <div class="mx-auto w-full max-w-xl rounded-2xl border border-red-100 bg-white p-6 text-center shadow-xl shadow-slate-200/70 sm:p-10">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-red-100 text-2xl font-bold text-red-700" aria-hidden="true">!</div>
            <p class="mt-5 text-sm font-semibold uppercase tracking-[0.2em] text-red-700">Reservation hold expired</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">Your conference room was released</h1>
            <p class="mt-3 text-sm leading-6 text-gray-600">The temporary hold ended before payment was completed. Choose another available space to start a new reservation.</p>
            <a href="{{ route('conference.index') }}" class="mt-7 flex min-h-12 w-full items-center justify-center rounded-xl bg-blue-600 px-4 py-3 text-base font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Browse conference rooms</a>
        </div>
    </section>
</x-guest-layout>
