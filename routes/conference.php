<?php

use App\Models\ConferenceBooking;
use App\Models\ConferenceRoom;
use App\Models\Payment;
use App\Services\CorporateCreditService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::get('/conference-rooms', function () {

    $rooms = ConferenceRoom::published()->with(['facilities' => fn ($query) => $query->published()])->where('is_available', true)->get();

    return view('conference.index', compact('rooms'));
})->name('conference.index');

Route::middleware('auth')->group(function () {

    Route::get('/conference-room/{room}/book',
        function (ConferenceRoom $room) {
            abort_unless($room->is_published, 404);

            return view('conference.book', compact('room'));
        })->name('conference.book');

    Route::post('/conference-room/book',
        function (Request $request) {
            $request->validate([
                'conference_room_id' => 'required',
                'booking_date' => 'required|date',
                'start_time' => 'required',
                'end_time' => 'required',
                'attendees' => 'required|integer|min:1',
                'promotion_code' => 'nullable|string|max:100',
            ]);

            $guest = auth()->user()->guest;
            $organization = app(CorporateCreditService::class)->organizationFor(auth()->user());

            $room = ConferenceRoom::published()->findOrFail(
                $request
                    ->conference_room_id
            );

            if (! $room->is_available) {
                return back()->withInput()->withErrors(['conference_room_id' => 'This conference room is unavailable.']);
            }

            $start =
                Carbon::parse(
                    $request->start_time
                );

            $end =
                Carbon::parse(
                    $request->end_time
                );

            if ($end <= $start) {

                return back()

                    ->withErrors([

                        'end_time' => 'End time must be after start time.',

                    ])

                    ->withInput();
            }

            $overlaps = ConferenceBooking::query()
                ->where('conference_room_id', $room->id)
                ->whereDate('booking_date', $request->booking_date)
                ->where(function ($query) {
                    $query->whereIn('status', ['confirmed', 'checked_in'])
                        ->orWhere(function ($pending) {
                            $pending->where('status', 'pending')->where('hold_until', '>', now());
                        });
                })
                ->where('start_time', '<', $request->end_time)
                ->where('end_time', '>', $request->start_time)
                ->exists();

            if ($overlaps) {
                return back()->withInput()->withErrors(['start_time' => 'This conference room is already booked for the selected time slot.']);
            }

            $hours =
                $start->diffInHours(
                    $end
                );

            $subtotal = $hours * $room->price_per_hour;
            $promotion = filled($request->promotion_code)
                ? \App\Models\Promotion::query()->where('code', strtoupper($request->promotion_code))->applicable((float) $subtotal)->first()
                : null;

            if (filled($request->promotion_code) && ! $promotion) {
                return back()->withInput()->withErrors(['promotion_code' => 'This discount code is not valid for this booking.']);
            }

            $billing = app(\App\Services\BillingService::class)->calculate(
                (float) $subtotal,
                $promotion?->discount_type,
                (float) ($promotion?->discount_value ?? 0),
            );

            $booking = ConferenceBooking::create([
                'conference_room_id' => $room->id,
                'guest_id' => $guest->id,
                'corporate_organization_id' => $organization?->id,
                'booking_date' => $request->booking_date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'attendees' => $request->attendees,
                'special_requests' => $request->special_requests,
                'subtotal' => $billing['subtotal'],
                'discount' => $billing['discount'],
                'vat' => $billing['vat'],
                'nhil' => $billing['nhil'],
                'service_charge' => $billing['serviceCharge'],
                'promotion_code' => $promotion?->code,
                'total_price' => $billing['total'],
                'status' => $organization ? 'confirmed' : 'pending',
                'payment_status' => 'pending',
                'hold_until' => $organization ? null : now()->addMinutes(15),
            ]);

            if ($booking->payment_status === 'pending'
                &&
                $booking->hold_until?->isPast()) {
                $booking->update([
                    'status' => 'expired',
                    'payment_status' => 'expired',
                ]);

                return redirect()->route('conference.expired');
            }

            if ($organization) {
                return redirect()->route('dashboard')->with('success', "Conference booking confirmed and billed to {$organization->name}.");
            }

            return redirect()->route('conference.payment', $booking->id);
        })->name('conference.booking.store');
});

Route::get('/conference-booking/{booking}/payment',
    function (ConferenceBooking $booking) {

        abort_unless($booking->guest_id === auth()->user()?->guest?->id, 403);

        return view('conference.payment', compact('booking'));
    })->middleware('auth')->name('conference.payment');

Route::post('/conference-booking/{booking}/pay',
    function (
        ConferenceBooking $booking
    ) {

        abort_unless($booking->guest_id === auth()->user()?->guest?->id, 403);

        if ($booking->payment_status === 'paid' || $booking->hold_until?->isPast()) {
            return back()
                ->with('error', 'This booking is no longer available for payment.');
        }

        $reference = 'CONF_'.Str::upper(Str::random(16));
        $booking->update(['transaction_reference' => $reference]);

        $response = Http::withToken(config('services.paystack.secretKey'))
            ->post(
                'https://api.paystack.co/transaction/initialize',
                [
                    'email' => auth()->user()->email,

                    'amount' => $booking->total_price * 100,

                    'reference' => $reference,

                    'metadata' => ['conference_booking_id' => $booking->id],

                    'callback_url' => route('conference.verify', $booking->id),
                ]
            )
            ->json();

        if (! isset($response['data']['authorization_url'])) {
            return back()
                ->with('error', 'Payment initialization failed.');
        }

        return redirect(
            $response['data']['authorization_url']
        );
    })->middleware('auth')->name('conference.pay');

Route::get('/conference-booking/{booking}/verify',

    function (ConferenceBooking $booking) {

        abort_unless($booking->guest_id === auth()->user()?->guest?->id, 403);

        $reference = request('reference');

        if (! $reference) {
            return redirect()
                ->route(
                    'conference.payment',
                    $booking->id
                )
                ->with(
                    'error',
                    'Missing payment reference.'
                );
        }

        $response =
            Http::withToken(
                config(
                    'services.paystack.secretKey'
                )
            )
                ->get(
                    "https://api.paystack.co/transaction/verify/{$reference}"
                )
                ->json();

        if (! isset($response['data']) || $response['data']['status'] !== 'success'
            || $reference !== $booking->transaction_reference
            || (int) $response['data']['amount'] !== (int) round($booking->total_price * 100)
            || (int) data_get($response, 'data.metadata.conference_booking_id') !== $booking->id) {
            return redirect()
                ->route(
                    'conference.payment',
                    $booking->id
                )
                ->with(
                    'error',
                    'Payment verification failed.'
                );
        }

        DB::transaction(function () use ($booking, $reference): void {
            $booking = ConferenceBooking::query()->lockForUpdate()->findOrFail($booking->id);

            if ($booking->payment_status === 'paid') {
                return;
            }

            $booking->update(['payment_status' => 'paid', 'status' => 'confirmed', 'hold_until' => null]);
            Payment::firstOrCreate(['transaction_reference' => $reference], [
                'conference_booking_id' => $booking->id,
                'guest_id' => $booking->guest_id,
                'amount' => $booking->total_price,
                'method' => 'paystack',
                'payment_status' => 'completed',
            ]);
        });

        return redirect()
            ->route(
                'dashboard'
            )
            ->with(
                'success',
                'Conference booking confirmed.'
            );
    })->middleware('auth')->name('conference.verify');

Route::get('/conference-booking/expired',
    function () {
        return view('conference.expired');
    })->name('conference.expired');

Route::post(

    '/conference-booking/{booking}/cancel',

    function (
        ConferenceBooking $booking
    ) {

        abort_unless($booking->guest_id === auth()->user()?->guest?->id, 403);

        if (

            $booking
                ->payment_status
            === 'paid'

        ) {

            return back()

                ->with(
                    'error',
                    'Paid bookings cannot be cancelled.'
                );
        }

        $booking->update([

            'status' => 'cancelled',

            'payment_status' => $booking->payment_status
                === 'paid'

                ? 'paid'

                : 'failed',

        ]);

        return redirect()
            ->route('conference.index')
            ->with('success', 'Conference booking cancelled. The room hold has been released.');
    }

)->middleware('auth')->name(
    'conference.cancel'
);

Route::get(

    '/conference-booking/{booking}/invoice',

    function (
        ConferenceBooking $booking
    ) {

        abort_unless($booking->guest_id === auth()->user()?->guest?->id, 403);

        $pdf =
            Pdf::loadView(

                'invoice.conference',

                compact(
                    'booking'
                )
            );

        return $pdf
            ->download(

                'conference_invoice.pdf'
            );
    }

)->middleware('auth')->name(
    'conference.invoice'
);
