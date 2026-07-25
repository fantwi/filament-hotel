<x-guest-layout>
    <section class="px-4 py-10 sm:px-6 sm:py-14">
        <div class="mx-auto max-w-4xl space-y-6"><div class="mb-8"><p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600">Guest account</p><h1 class="mt-2 text-3xl font-bold tracking-tight text-gray-900">Profile settings</h1></div>
            <div class="rounded-2xl bg-white p-5 shadow-xl shadow-slate-200/70 ring-1 ring-slate-900/5 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="rounded-2xl bg-white p-5 shadow-xl shadow-slate-200/70 ring-1 ring-slate-900/5 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="rounded-2xl bg-white p-5 shadow-xl shadow-slate-200/70 ring-1 ring-slate-900/5 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </section>
</x-guest-layout>
