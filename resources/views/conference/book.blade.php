<x-guest-layout>
    @php
        $billingPreview = session('conference_billing_preview');
    @endphp
    <section class="px-4 py-10 sm:px-6 sm:py-14">
        <div class="mx-auto grid w-full max-w-5xl gap-6 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">
            <div>
                <div class="mb-7 text-center sm:text-left">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600">Conference reservation</p>
                    <h1 class="mt-2 text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">Book {{ $room->name }}</h1>
                    <p class="mt-2 text-sm leading-6 text-gray-600">Plan your event in a space for up to {{ $room->capacity }} attendees.</p>
                </div>

                @if (session('success'))<div class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4 text-green-800">{{ session('success') }}</div>@endif
                @if ($errors->any())<div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-red-700"><ul class="list-inside list-disc space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

                <form method="POST" action="{{ route('conference.booking.store') }}" class="rounded-2xl bg-white p-5 shadow-xl shadow-slate-200/70 ring-1 ring-slate-900/5 sm:p-8">
                    @csrf
                    <input type="hidden" name="conference_room_id" value="{{ $room->id }}">

                    <div class="mb-6 rounded-xl bg-slate-50 p-4"><p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Hourly rate</p><p class="mt-1 text-lg font-bold text-gray-900">GHS {{ number_format($room->price_per_hour, 2) }} <span class="text-sm font-normal text-gray-600">per hour</span></p></div>

                    <div class="space-y-5">
                        <div><label for="booking_date" class="block text-sm font-semibold text-gray-800">Event date</label><input id="booking_date" type="date" name="booking_date" min="{{ today()->toDateString() }}" value="{{ old('booking_date') }}" required class="mt-2 block min-h-12 w-full rounded-xl border-gray-300 px-4 text-base shadow-sm focus:border-blue-500 focus:ring-blue-500"></div>
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div><label for="start_time" class="block text-sm font-semibold text-gray-800">Start time</label><input id="start_time" type="time" name="start_time" value="{{ old('start_time') }}" required class="mt-2 block min-h-12 w-full rounded-xl border-gray-300 px-4 text-base shadow-sm focus:border-blue-500 focus:ring-blue-500"></div>
                            <div><label for="end_time" class="block text-sm font-semibold text-gray-800">End time</label><input id="end_time" type="time" name="end_time" value="{{ old('end_time') }}" required class="mt-2 block min-h-12 w-full rounded-xl border-gray-300 px-4 text-base shadow-sm focus:border-blue-500 focus:ring-blue-500"></div>
                        </div>
                        <div><label for="attendees" class="block text-sm font-semibold text-gray-800">Number of attendees</label><input id="attendees" type="number" name="attendees" value="{{ old('attendees', 1) }}" min="1" max="{{ $room->capacity }}" required class="mt-2 block min-h-12 w-full rounded-xl border-gray-300 px-4 text-base shadow-sm focus:border-blue-500 focus:ring-blue-500"></div>
                        <div>
                            <label for="promotion_code" class="block text-sm font-semibold text-gray-800">Discount code <span class="font-normal text-gray-500">(optional)</span></label>
                            <div class="mt-2 flex flex-col gap-2 sm:flex-row"><input id="promotion_code" type="text" name="promotion_code" value="{{ old('promotion_code') }}" class="min-h-12 flex-1 rounded-xl border-gray-300 px-4 text-base shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Enter a discount code"><button type="submit" name="apply_discount" value="1" class="min-h-12 rounded-xl border border-blue-600 px-4 text-sm font-semibold text-blue-700">Apply discount</button></div>
                            <p class="mt-2 text-xs leading-5 text-gray-500">Apply the code to validate it and update the estimate. It is checked again before the booking is created.</p>
                            @error('promotion_code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div><label for="special_requests" class="block text-sm font-semibold text-gray-800">Special requests <span class="font-normal text-gray-500">(optional)</span></label><textarea id="special_requests" name="special_requests" rows="4" class="mt-2 block w-full rounded-xl border-gray-300 px-4 py-3 text-base shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('special_requests') }}</textarea></div>
                        @if ($corporateOrganization)
                            <fieldset class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                                <legend class="px-1 text-sm font-semibold text-blue-900">Payment preference</legend>
                                <p class="mt-1 text-sm text-blue-800">Choose payment now or billing to {{ $corporateOrganization->name }}.</p>
                                <label class="mt-4 flex cursor-pointer items-start gap-3 rounded-lg bg-white p-3"><input type="radio" name="use_corporate_credit" value="0" @checked((string) old('use_corporate_credit', '0') !== '1')><span><span class="block font-semibold text-gray-900">Pay now</span><span class="text-sm text-gray-600">Continue to Paystack after this booking is held.</span></span></label>
                                <label class="mt-3 flex cursor-pointer items-start gap-3 rounded-lg bg-white p-3"><input type="radio" name="use_corporate_credit" value="1" @checked((string) old('use_corporate_credit') === '1')><span><span class="block font-semibold text-gray-900">Bill to corporate account</span><span class="text-sm text-gray-600">Confirm now and charge {{ $corporateOrganization->name }} under its payment terms.</span></span></label>
                                @error('use_corporate_credit')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                            </fieldset>
                        @endif
                    </div>

                    <button type="submit" class="mt-7 flex min-h-12 w-full items-center justify-center rounded-xl bg-blue-600 px-4 py-3 text-base font-semibold text-white shadow-sm transition hover:bg-blue-700">Continue</button>
                </form>
            </div>

            <aside id="conference-billing-summary" class="rounded-2xl border border-blue-100 bg-white p-5 shadow-lg shadow-slate-200/60 lg:sticky lg:top-6" aria-live="polite">
                <p class="text-sm font-semibold uppercase tracking-[0.16em] text-blue-600">Booking summary</p>
                <h2 class="mt-2 text-xl font-bold text-gray-900">Estimated total</h2>
                <p id="conference-estimate-note" class="mt-2 text-sm text-gray-600">Select a valid time range to estimate your booking.</p>
                <dl class="mt-5 space-y-3 border-t border-slate-200 pt-4 text-sm">
                    <div class="flex justify-between gap-4"><dt>Subtotal</dt><dd id="conference-subtotal">GHS 0.00</dd></div>
                    <div class="flex justify-between gap-4 text-green-700"><dt>Discount</dt><dd id="conference-discount">- GHS 0.00</dd></div>
                    <div class="flex justify-between gap-4 font-medium"><dt>Net after discount</dt><dd id="conference-net">GHS 0.00</dd></div>
                    <div class="flex justify-between gap-4"><dt>Service charge ({{ number_format($billingRates['service_charge'], 2) }}%)</dt><dd id="conference-service">GHS 0.00</dd></div>
                    <div class="flex justify-between gap-4"><dt>VAT ({{ number_format($billingRates['vat'], 2) }}%)</dt><dd id="conference-vat">GHS 0.00</dd></div>
                    <div class="flex justify-between gap-4"><dt>NHIL ({{ number_format($billingRates['nhil'], 2) }}%)</dt><dd id="conference-nhil">GHS 0.00</dd></div>
                </dl>
                <div class="mt-5 flex justify-between border-t border-slate-200 pt-4 text-lg font-bold text-gray-900"><span>Total</span><span id="conference-total">GHS 0.00</span></div>
            </aside>
        </div>
    </section>

    <script>
        (() => {
            const pricePerHour = Number(@json((float) $room->price_per_hour));
            const rates = @json($billingRates);
            const preview = @json($billingPreview);
            const input = {
                date: document.getElementById('booking_date'),
                start: document.getElementById('start_time'),
                end: document.getElementById('end_time'),
            };
            const text = (id) => document.getElementById(id);
            const money = (amount) => 'GHS ' + Number(amount || 0).toFixed(2);

            const estimate = () => {
                if (preview && preview.booking_date === input.date.value && preview.start_time === input.start.value && preview.end_time === input.end.value) return preview;
                if (!input.start.value || !input.end.value) return null;

                const start = new Date('2000-01-01T' + input.start.value);
                const end = new Date('2000-01-01T' + input.end.value);
                const hours = Math.floor((end - start) / 3600000);
                if (hours < 1) return null;

                const subtotal = hours * pricePerHour;
                const vat = subtotal * Number(rates.vat) / 100;
                const nhil = subtotal * Number(rates.nhil) / 100;
                const service = subtotal * Number(rates.service_charge) / 100;

                return { subtotal: subtotal, discount: 0, net: subtotal, vat: vat, nhil: nhil, service_charge: service, total: subtotal + vat + nhil + service };
            };

            const render = () => {
                const value = estimate();
                if (!value) {
                    ['subtotal', 'net', 'service', 'vat', 'nhil', 'total'].forEach((name) => text('conference-' + name).textContent = 'GHS 0.00');
                    text('conference-discount').textContent = '- GHS 0.00';
                    text('conference-estimate-note').textContent = 'Select a valid time range to estimate your booking.';
                    return;
                }

                text('conference-subtotal').textContent = money(value.subtotal);
                text('conference-discount').textContent = '- ' + money(value.discount);
                text('conference-net').textContent = money(value.net);
                text('conference-service').textContent = money(value.service_charge);
                text('conference-vat').textContent = money(value.vat);
                text('conference-nhil').textContent = money(value.nhil);
                text('conference-total').textContent = money(value.total);
                text('conference-estimate-note').textContent = value.promotion_code ? 'Discount code ' + value.promotion_code + ' is applied.' : 'Taxes and service charge are included in this estimate.';
            };

            Object.values(input).forEach((field) => field.addEventListener('input', render));
            render();
        })();
    </script>
</x-guest-layout>
