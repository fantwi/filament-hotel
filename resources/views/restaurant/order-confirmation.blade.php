<x-guest-layout>
    <div class="mx-auto max-w-2xl px-4 py-8 sm:px-6 sm:py-12">
        @if (session('success'))<div class="mb-6 rounded-lg bg-green-100 p-4 text-green-700">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="mb-6 rounded-lg bg-red-100 p-4 text-red-700">{{ session('error') }}</div>@endif
        <div class="rounded-2xl bg-white p-5 shadow-xl shadow-slate-200/70 ring-1 ring-slate-900/5 sm:p-8">
            <h1 class="break-words text-2xl font-bold sm:text-3xl">Order {{ $order->order_number }}</h1>
            <p class="mt-2 text-gray-600">Status: <strong>{{ ucfirst($order->status) }}</strong></p>
            @if ($order->payment_method === 'corporate_account')
                <p class="mt-1 text-gray-600">Billing: <strong>Corporate account</strong></p>
            @else
                <p class="mt-1 text-gray-600">Payment: <strong>{{ ucfirst($order->payment_status) }}</strong></p>
            @endif
            <dl class="mt-6 space-y-2 border-t pt-4 text-sm">
                <div class="flex justify-between"><dt>Subtotal</dt><dd>GHS {{ number_format($order->subtotal, 2) }}</dd></div>
                <div class="flex justify-between text-green-700"><dt>Discount{{ $order->promotion_code ? ' ('.$order->promotion_code.')' : '' }}</dt><dd>- GHS {{ number_format($order->discount ?? 0, 2) }}</dd></div>
                <div class="flex justify-between font-medium"><dt>Net after discount</dt><dd>GHS {{ number_format($order->subtotal - $order->discount, 2) }}</dd></div>
                <div class="flex justify-between"><dt>Service charge</dt><dd>GHS {{ number_format($order->service_charge, 2) }}</dd></div>
                <div class="flex justify-between"><dt>VAT</dt><dd>GHS {{ number_format($order->vat ?? 0, 2) }}</dd></div>
                <div class="flex justify-between"><dt>NHIL</dt><dd>GHS {{ number_format($order->nhil ?? 0, 2) }}</dd></div>
            </dl>
            <p class="mt-5 border-t pt-4 text-2xl font-bold">Total: GHS {{ number_format($order->total, 2) }}</p>
            @php($isCorporateOrder = $order->corporate_organization_id || $order->payment_method === 'corporate_account')
            @if ($isCorporateOrder && $order->payment_status !== 'completed')
                <p class="mt-6 rounded-lg bg-amber-50 p-4 text-amber-800">Corporate billing: this order is awaiting settlement under your organization's payment terms. No Paystack payment is required from you.</p>
            @endif
            @if (! $isCorporateOrder && $order->payment_status !== 'completed')
                <form action="{{ route('restaurant.orders.pay', $order) }}" method="POST" class="mt-6">@csrf<button class="flex min-h-12 w-full items-center justify-center rounded-xl bg-blue-600 py-3 font-semibold text-white">Pay securely with Paystack</button></form>
            @endif
            @if ($order->payment_status !== 'completed')
                <form action="{{ route('restaurant.orders.cancel', $order) }}" method="POST" class="mt-3">@csrf<button class="flex min-h-12 w-full items-center justify-center rounded-xl border border-red-200 bg-white py-3 font-semibold text-red-700">Cancel order</button></form>
            @else
                <p class="mt-6 rounded-lg bg-blue-50 p-4 text-blue-700">The kitchen has received your order and will begin preparation shortly.</p>
            @endif
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                <a href="{{ route('restaurant.menu') }}" class="flex min-h-12 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-3 font-semibold text-slate-700 transition hover:bg-slate-50">Back to menu</a>
                <a href="{{ route('dashboard') }}" class="flex min-h-12 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-3 font-semibold text-slate-700 transition hover:bg-slate-50">Back to dashboard</a>
            </div>
        </div>
    </div>
</x-guest-layout>
