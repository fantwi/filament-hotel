<x-guest-layout>
    <section class="bg-slate-950 py-14 text-white sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <p class="text-sm font-semibold uppercase tracking-[0.22em] text-amber-300">Contact</p>
            <h1 class="mt-3 max-w-2xl text-4xl font-bold tracking-tight sm:text-5xl">We would love to hear from you.</h1>
            <p class="mt-5 max-w-2xl text-lg leading-8 text-slate-200">Reach out for reservations, room enquiries, conference bookings, or any help planning your stay.</p>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-16 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-5 lg:gap-12">
            <div class="lg:col-span-2">
                <p class="text-sm font-semibold uppercase tracking-widest text-indigo-600">Get in touch</p>
                <h2 class="mt-2 text-3xl font-bold text-gray-900">Here when you need us</h2>
                <p class="mt-4 leading-7 text-gray-600">Our team is ready to help make your visit, meal, or event a smooth experience.</p>

                <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
                    <div class="rounded-xl border border-gray-200 p-5"><p class="text-sm font-medium text-gray-500">Visit us</p><p class="mt-2 font-semibold text-gray-900">Cape Coast, Ghana</p></div>
                    <div class="rounded-xl border border-gray-200 p-5"><p class="text-sm font-medium text-gray-500">Call us</p><a href="tel:+233551234567" class="mt-2 inline-block font-semibold text-indigo-700 hover:text-indigo-900">+233 55 123 4567</a></div>
                    <div class="rounded-xl border border-gray-200 p-5"><p class="text-sm font-medium text-gray-500">Email us</p><a href="mailto:info@myhotel.com" class="mt-2 inline-block break-all font-semibold text-indigo-700 hover:text-indigo-900">info@myhotel.com</a></div>
                    <div class="rounded-xl border border-gray-200 p-5"><p class="text-sm font-medium text-gray-500">Hours</p><p class="mt-2 font-semibold text-gray-900">Open 24/7</p></div>
                </div>

                <div class="mt-8 overflow-hidden rounded-xl border border-gray-200">
                    <iframe title="My Hotel location" src="https://maps.google.com/maps?q=Cape%20Coast&t=&z=13&ie=UTF8&iwloc=&output=embed" class="h-64 w-full sm:h-80" loading="lazy"></iframe>
                </div>
            </div>

            <div class="rounded-2xl bg-white p-5 shadow-xl ring-1 ring-gray-950/5 sm:p-8 lg:col-span-3">
                <h2 class="text-2xl font-bold text-gray-900">Send a message</h2>
                <p class="mt-2 text-gray-600">Complete the form and our team will respond as soon as possible.</p>

                @if (session('success'))
                    <div class="mt-6 rounded-lg bg-green-50 p-4 text-green-700">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="mt-6 rounded-lg border border-red-200 bg-red-50 p-4 text-red-700"><ul class="list-inside list-disc space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif

                <form method="POST" action="{{ route('contact') }}" class="mt-7 space-y-5">
                    @csrf
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div><label for="name" class="font-semibold text-gray-800">Your name</label><input id="name" type="text" name="name" value="{{ old('name') }}" class="mt-2 min-h-12 w-full rounded-xl border-gray-300 px-4 text-base" autocomplete="name" required></div>
                        <div><label for="email" class="font-semibold text-gray-800">Email address</label><input id="email" type="email" name="email" value="{{ old('email') }}" class="mt-2 min-h-12 w-full rounded-xl border-gray-300 px-4 text-base" autocomplete="email" required></div>
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div><label for="phone_number" class="font-semibold text-gray-800">Phone number <span class="font-normal text-gray-500">(optional)</span></label><input id="phone_number" type="tel" name="phone_number" value="{{ old('phone_number') }}" class="mt-2 w-full rounded-lg border-gray-300 p-3" autocomplete="tel"></div>
                        <div><label for="subject" class="font-semibold text-gray-800">Subject</label><input id="subject" type="text" name="subject" value="{{ old('subject') }}" class="mt-2 w-full rounded-lg border-gray-300 p-3" required></div>
                    </div>
                    <div><label for="message" class="font-semibold text-gray-800">How can we help?</label><textarea id="message" name="message" rows="6" class="mt-2 w-full rounded-lg border-gray-300 p-3" required>{{ old('message') }}</textarea></div>
                    <button type="submit" class="flex min-h-12 w-full items-center justify-center rounded-xl bg-indigo-600 px-6 py-3 font-semibold text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto">Send message</button>
                </form>
            </div>
        </div>
    </section>
</x-guest-layout>
