<x-guest-layout>
    @php
        $billingPreview = session('restaurant_reservation_billing_preview');
    @endphp
    <section class="px-4 py-10 sm:px-6 sm:py-14">
        <div class="mx-auto grid w-full max-w-6xl gap-6 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">
            <div>
                <div class="mb-7 text-center sm:text-left">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-indigo-600">Restaurant reservation</p>
                    <h1 class="mt-2 text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">Reserve a table</h1>
                    <p class="mt-2 text-sm leading-6 text-gray-600">Choose your table, dining date, and time. Your reservation is held for 15 minutes while payment is completed.</p>
                </div>

                @if (session('success'))
                    <div class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4 text-green-800">{{ session('success') }}</div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-red-700">
                        <ul class="list-inside list-disc space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif

                @if ($tables->isEmpty())
                    <div class="rounded-xl bg-white p-6 shadow">
                        <h2 class="text-xl font-bold">No tables are currently available.</h2>
                        <p class="mt-2 text-gray-600">Please contact us or check back shortly.</p>
                        <a href="{{ route('restaurant') }}" class="mt-5 inline-flex rounded-lg bg-indigo-600 px-5 py-3 font-semibold text-white">Back to Restaurant</a>
                    </div>
                @else
                    <form action="{{ route('restaurant.reserve.store') }}" method="POST" class="rounded-2xl bg-white p-5 shadow-xl shadow-slate-200/70 ring-1 ring-slate-900/5 sm:p-8">
                        @csrf

                        <div class="hidden" aria-hidden="true">
                            <label for="website">Website</label>
                            <input id="website" type="text" name="website" tabindex="-1" autocomplete="off">
                        </div>

                        <div>
                            <label for="restaurant_table_id" class="block text-sm font-semibold text-gray-800">Restaurant table</label>
                            <select id="restaurant_table_id" name="restaurant_table_id" class="mt-2 block min-h-12 w-full rounded-xl border-gray-300 px-4 text-base shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                <option value="">Select a table</option>
                                @foreach ($tables as $table)
                                    <option value="{{ $table->id }}" data-reservation-fee="{{ (float) $table->reservation_fee }}" @selected(old('restaurant_table_id', request('table')) == $table->id)>
                                        {{ $table->table_number }} - seats {{ $table->capacity }}{{ $table->location ? ' (' . $table->location . ')' : '' }} - GHS {{ number_format($table->reservation_fee, 2) }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-2 text-xs text-gray-500">The table reservation fee is shown before taxes, service charge, and any discount.</p>
                        </div>

                        <div class="mt-6 grid gap-5 sm:grid-cols-2">
                            <div><label for="guest_name" class="block text-sm font-semibold text-gray-800">Full name</label><input id="guest_name" type="text" name="guest_name" value="{{ old('guest_name', auth()->user()?->name) }}" class="mt-2 block min-h-12 w-full rounded-xl border-gray-300 px-4 text-base shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required></div>
                            <div><label for="guest_email" class="block text-sm font-semibold text-gray-800">Email address</label><input id="guest_email" type="email" name="guest_email" value="{{ old('guest_email', auth()->user()?->email) }}" class="mt-2 block min-h-12 w-full rounded-xl border-gray-300 px-4 text-base shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required></div>
                            <div><label for="guest_phone" class="block text-sm font-semibold text-gray-800">Phone number</label><input id="guest_phone" type="tel" name="guest_phone" value="{{ old('guest_phone', auth()->user()?->phone_number) }}" class="mt-2 block min-h-12 w-full rounded-xl border-gray-300 px-4 text-base shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required></div>
                            <div><label for="number_of_guests" class="block text-sm font-semibold text-gray-800">Number of guests</label><input id="number_of_guests" type="number" name="number_of_guests" min="1" value="{{ old('number_of_guests', 1) }}" class="mt-2 block min-h-12 w-full rounded-xl border-gray-300 px-4 text-base shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required></div>
                            <div><label for="reservation_date" class="block text-sm font-semibold text-gray-800">Reservation date</label><input id="reservation_date" type="date" name="reservation_date" min="{{ today()->toDateString() }}" value="{{ old('reservation_date') }}" class="mt-2 block min-h-12 w-full rounded-xl border-gray-300 px-4 text-base shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required></div>
                            <div><label for="reservation_time" class="block text-sm font-semibold text-gray-800">Reservation time</label><input id="reservation_time" type="time" name="reservation_time" value="{{ old('reservation_time') }}" class="mt-2 block min-h-12 w-full rounded-xl border-gray-300 px-4 text-base shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required></div>
                        </div>

                        <div class="mt-6">
                            <label for="promotion_code" class="block text-sm font-semibold text-gray-800">Discount code <span class="font-normal text-gray-500">(optional)</span></label>
                            <div class="mt-2 flex flex-col gap-2 sm:flex-row">
                                <input id="promotion_code" type="text" name="promotion_code" value="{{ old('promotion_code') }}" class="min-h-12 flex-1 rounded-xl border-gray-300 px-4 text-base shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Enter a discount code">
                                <button type="submit" name="apply_discount" value="1" class="min-h-12 rounded-xl border border-indigo-600 px-4 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-50">Apply discount</button>
                            </div>
                            <p class="mt-2 text-xs leading-5 text-gray-500">Apply the code to validate it and update the estimate. It is checked again before the reservation is created.</p>
                            @error('promotion_code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="mt-6"><label for="special_requests" class="block text-sm font-semibold text-gray-800">Special requests <span class="font-normal text-gray-500">(optional)</span></label><textarea id="special_requests" name="special_requests" rows="4" class="mt-2 block w-full rounded-xl border-gray-300 px-4 py-3 text-base shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Accessibility needs, dietary requests, or celebrations">{{ old('special_requests') }}</textarea></div>

                        @if ($corporateOrganization)
                            <fieldset class="mt-6 rounded-xl border border-indigo-200 bg-indigo-50 p-4">
                                <legend class="px-1 text-sm font-semibold text-indigo-900">Payment preference</legend>
                                <p class="mt-1 text-sm text-indigo-800">Choose payment now or billing to {{ $corporateOrganization->name }}.</p>
                                <label class="mt-4 flex cursor-pointer items-start gap-3 rounded-lg bg-white p-3"><input type="radio" name="use_corporate_credit" value="0" @checked((string) old('use_corporate_credit', '0') !== '1')><span><span class="block font-semibold text-gray-900">Pay now</span><span class="text-sm text-gray-600">Hold the table and continue to Paystack.</span></span></label>
                                <label class="mt-3 flex cursor-pointer items-start gap-3 rounded-lg bg-white p-3"><input type="radio" name="use_corporate_credit" value="1" @checked((string) old('use_corporate_credit') === '1')><span><span class="block font-semibold text-gray-900">Bill to corporate account</span><span class="text-sm text-gray-600">Confirm now and charge {{ $corporateOrganization->name }} under its payment terms.</span></span></label>
                                @error('use_corporate_credit')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                            </fieldset>
                        @endif

                        <button type="submit" class="mt-7 flex min-h-12 w-full items-center justify-center rounded-xl bg-indigo-600 px-4 py-3 text-base font-semibold text-white shadow-sm transition hover:bg-indigo-700">Continue</button>
                    </form>
                @endif
            </div>

            <aside id="restaurant-reservation-billing-summary" class="rounded-2xl border border-indigo-100 bg-white p-5 shadow-lg shadow-slate-200/60 lg:sticky lg:top-6" aria-live="polite">
                <p class="text-sm font-semibold uppercase tracking-[0.16em] text-indigo-600">Booking summary</p>
                <h2 class="mt-2 text-xl font-bold text-gray-900">Estimated total</h2>
                <p id="restaurant-reservation-estimate-note" class="mt-2 text-sm text-gray-600">Select a table to estimate your reservation.</p>
                <dl class="mt-5 space-y-3 border-t border-slate-200 pt-4 text-sm">
                    <div class="flex justify-between gap-4"><dt>Subtotal</dt><dd id="restaurant-reservation-subtotal">GHS 0.00</dd></div>
                    <div class="flex justify-between gap-4 text-green-700"><dt>Discount</dt><dd id="restaurant-reservation-discount">- GHS 0.00</dd></div>
                    <div class="flex justify-between gap-4 font-medium"><dt>Net after discount</dt><dd id="restaurant-reservation-net">GHS 0.00</dd></div>
                    <div class="flex justify-between gap-4"><dt>Service charge ({{ number_format($billingRates['service_charge'], 2) }}%)</dt><dd id="restaurant-reservation-service">GHS 0.00</dd></div>
                    <div class="flex justify-between gap-4"><dt>VAT ({{ number_format($billingRates['vat'], 2) }}%)</dt><dd id="restaurant-reservation-vat">GHS 0.00</dd></div>
                    <div class="flex justify-between gap-4"><dt>NHIL ({{ number_format($billingRates['nhil'], 2) }}%)</dt><dd id="restaurant-reservation-nhil">GHS 0.00</dd></div>
                </dl>
                <div class="mt-5 flex justify-between border-t border-slate-200 pt-4 text-lg font-bold text-gray-900"><span>Total</span><span id="restaurant-reservation-total">GHS 0.00</span></div>
            </aside>
        </div>
    </section>

    <script>
        (() => {
            const rates = @json($billingRates);
            const preview = @json($billingPreview);
            const table = document.getElementById('restaurant_table_id');
            const text = (id) => document.getElementById(id);
            const money = (amount) => 'GHS ' + Number(amount || 0).toFixed(2);

            const estimate = () => {
                const option = table.options[table.selectedIndex];
                const tableId = table.value;

                if (preview && String(preview.restaurant_table_id) === String(tableId)) {
                    return preview;
                }

                if (!tableId) {
                    return null;
                }

                const subtotal = Number(option.dataset.reservationFee || 0);
                const vat = subtotal * Number(rates.vat) / 100;
                const nhil = subtotal * Number(rates.nhil) / 100;
                const service = subtotal * Number(rates.service_charge) / 100;

                return { subtotal: subtotal, discount: 0, net: subtotal, vat: vat, nhil: nhil, service_charge: service, total: subtotal + vat + nhil + service };
            };

            const render = () => {
                const value = estimate();

                if (!value) {
                    ['subtotal', 'net', 'service', 'vat', 'nhil', 'total'].forEach((name) => text('restaurant-reservation-' + name).textContent = 'GHS 0.00');
                    text('restaurant-reservation-discount').textContent = '- GHS 0.00';
                    text('restaurant-reservation-estimate-note').textContent = 'Select a table to estimate your reservation.';
                    return;
                }

                text('restaurant-reservation-subtotal').textContent = money(value.subtotal);
                text('restaurant-reservation-discount').textContent = '- ' + money(value.discount);
                text('restaurant-reservation-net').textContent = money(value.net);
                text('restaurant-reservation-service').textContent = money(value.service_charge);
                text('restaurant-reservation-vat').textContent = money(value.vat);
                text('restaurant-reservation-nhil').textContent = money(value.nhil);
                text('restaurant-reservation-total').textContent = money(value.total);
                text('restaurant-reservation-estimate-note').textContent = value.promotion_code ? 'Discount code ' + value.promotion_code + ' is applied.' : 'Taxes and service charge are included in this estimate.';
            };

            table.addEventListener('change', render);
            render();
        })();
    </script>
</x-guest-layout>
