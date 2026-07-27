<x-guest-layout>
    <main class="bg-slate-50 text-slate-900 transition-colors dark:bg-[#161b48] dark:text-slate-100">
        <section class="relative isolate overflow-hidden bg-[#161b48] py-12 text-white sm:py-20">
            <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_85%_20%,rgba(56,189,248,0.26),transparent_26%),radial-gradient(circle_at_10%_90%,rgba(251,191,36,0.18),transparent_30%)]"></div>
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <p class="inline-flex rounded-full border border-amber-200/25 bg-white/10 px-3 py-1 text-xs font-bold uppercase tracking-[0.2em] text-amber-200 backdrop-blur">Contact My Hotel</p>
                <h1 class="mt-5 max-w-2xl text-3xl font-bold tracking-tight sm:text-5xl">We would love to hear from you.</h1>
                <p class="mt-4 max-w-2xl text-base leading-7 text-indigo-100 sm:text-lg sm:leading-8">Ask about a stay, reserve a table, plan an event, or let us help you make your visit easy.</p>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-16 lg:px-8">
            <div class="grid gap-6 lg:grid-cols-5 lg:gap-10">
                <aside class="lg:col-span-2">
                    <p class="text-sm font-bold uppercase tracking-[0.18em] text-indigo-600 dark:text-indigo-300">Get in touch</p>
                    <h2 class="mt-3 text-2xl font-bold tracking-tight sm:text-3xl">Here when you need us</h2>
                    <p class="mt-4 leading-7 text-slate-600 dark:text-slate-300">Our team is ready to help make your visit, meal, or event a smooth experience.</p>

                    <div class="mt-7 grid gap-3 min-[440px]:grid-cols-2 lg:grid-cols-1">
                        <a href="https://maps.google.com/?q=Cape+Coast,+Ghana" target="_blank" rel="noopener" class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70 transition hover:-translate-y-0.5 hover:shadow-lg dark:bg-slate-900 dark:ring-white/10"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Visit us</p><p class="mt-2 font-bold">Cape Coast, Ghana</p></a>
                        <a href="tel:+233551234567" class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70 transition hover:-translate-y-0.5 hover:shadow-lg dark:bg-slate-900 dark:ring-white/10"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Call us</p><p class="mt-2 font-bold text-indigo-700 dark:text-indigo-300">+233 55 123 4567</p></a>
                        <a href="mailto:info@myhotel.com" class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70 transition hover:-translate-y-0.5 hover:shadow-lg dark:bg-slate-900 dark:ring-white/10"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Email us</p><p class="mt-2 break-all font-bold text-indigo-700 dark:text-indigo-300">info@myhotel.com</p></a>
                        <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70 dark:bg-slate-900 dark:ring-white/10"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Hours</p><p class="mt-2 font-bold">Open 24/7</p></div>
                    </div>

                    <div class="mt-6 overflow-hidden rounded-2xl ring-1 ring-slate-200/70 dark:ring-white/10"><iframe title="My Hotel location" src="https://maps.google.com/maps?q=Cape%20Coast&t=&z=13&ie=UTF8&iwloc=&output=embed" class="h-56 w-full sm:h-72" loading="lazy"></iframe></div>
                </aside>

                <section class="rounded-3xl bg-white p-5 shadow-xl shadow-slate-200/60 ring-1 ring-slate-200/70 dark:bg-slate-900 dark:shadow-none dark:ring-white/10 sm:p-8 lg:col-span-3">
                    <p class="text-sm font-bold uppercase tracking-[0.18em] text-indigo-600 dark:text-indigo-300">Send a message</p>
                    <h2 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">How can we help?</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Send us the details and our team will respond as soon as possible.</p>

                    @if (session('success'))
                        <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 dark:border-emerald-400/20 dark:bg-emerald-400/10 dark:text-emerald-100">{{ session('success') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800 dark:border-rose-400/20 dark:bg-rose-400/10 dark:text-rose-100"><ul class="list-inside list-disc space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                    @endif

                    <form method="POST" action="{{ route('contact') }}" class="mt-7 space-y-5">@csrf
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div><label for="name" class="text-sm font-semibold">Your name</label><input id="name" type="text" name="name" value="{{ old('name') }}" class="mt-2 block min-h-12 w-full rounded-xl border-slate-300 bg-white px-4 text-base text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white" autocomplete="name" required></div>
                            <div><label for="email" class="text-sm font-semibold">Email address</label><input id="email" type="email" name="email" value="{{ old('email') }}" class="mt-2 block min-h-12 w-full rounded-xl border-slate-300 bg-white px-4 text-base text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white" autocomplete="email" required></div>
                        </div>
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div><label for="phone_number" class="text-sm font-semibold">Phone number <span class="font-normal text-slate-500 dark:text-slate-400">(optional)</span></label><input id="phone_number" type="tel" name="phone_number" value="{{ old('phone_number') }}" class="mt-2 block min-h-12 w-full rounded-xl border-slate-300 bg-white px-4 text-base text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white" autocomplete="tel"></div>
                            <div><label for="subject" class="text-sm font-semibold">Subject</label><input id="subject" type="text" name="subject" value="{{ old('subject') }}" class="mt-2 block min-h-12 w-full rounded-xl border-slate-300 bg-white px-4 text-base text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white" required></div>
                        </div>
                        <div><label for="message" class="text-sm font-semibold">How can we help?</label><textarea id="message" name="message" rows="6" class="mt-2 block w-full rounded-xl border-slate-300 bg-white px-4 py-3 text-base text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white" required>{{ old('message') }}</textarea></div>
                        <button type="submit" class="flex min-h-12 w-full items-center justify-center rounded-xl bg-indigo-600 px-6 py-3 font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 sm:w-auto">Send message</button>
                    </form>
                </section>
            </div>
        </section>
    </main>
</x-guest-layout>
