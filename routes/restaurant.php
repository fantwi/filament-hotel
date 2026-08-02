<?php

use App\Http\Controllers\RestaurantCartController;
use App\Http\Controllers\RestaurantCheckoutController;
use App\Http\Controllers\RestaurantOrderPaymentController;
use App\Http\Controllers\RestaurantPageController;
use App\Http\Controllers\RestaurantReservationController;
use App\Http\Controllers\RestaurantTableOrderController;
use App\Http\Controllers\RestaurantTableQrController;
use App\Mail\RestaurantPaymentReceived;
use App\Models\Payment;
use App\Models\RestaurantReservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::get('/restaurant', [RestaurantPageController::class, 'index'])->name('restaurant');
Route::get('/restaurant/tables', [RestaurantPageController::class, 'tables'])->name('restaurant.tables');
Route::get('/restaurant/gallery', [RestaurantPageController::class, 'gallery'])->name('restaurant.gallery');
Route::get('/restaurant/menu', [RestaurantPageController::class, 'menu'])->name('restaurant.menu');

Route::get('/table/{table:qr_token}/menu', [RestaurantTableOrderController::class, 'menu'])
    ->middleware('throttle:60,1')
    ->name('restaurant.table.menu');
Route::post('/restaurant/table-session/leave', [RestaurantTableOrderController::class, 'leaveTable'])
    ->name('restaurant.table.leave');
Route::middleware('auth')->get('/admin/restaurant-tables/{table}/qr', [RestaurantTableQrController::class, 'print'])
    ->name('restaurant.tables.qr.print');

Route::post('/cart/add/{item}', [RestaurantCartController::class, 'add'])->name('cart.add');
Route::get('/cart', [RestaurantCartController::class, 'index'])->name('cart.index');
Route::post('/cart/update/{item}', [RestaurantCartController::class, 'update'])->name('cart.update');
Route::delete('/cart/remove/{item}', [RestaurantCartController::class, 'remove'])->name('cart.remove');
Route::get('/restaurant/checkout', [RestaurantCheckoutController::class, 'index'])->name('restaurant.checkout');
Route::post('/restaurant/checkout', [RestaurantCheckoutController::class, 'store'])->name('restaurant.checkout.store');
Route::get('/restaurant/orders/{order}/confirmation', [RestaurantOrderPaymentController::class, 'confirmation'])->name('restaurant.orders.confirmation');
Route::post('/restaurant/orders/{order}/pay', [RestaurantOrderPaymentController::class, 'initialize'])->name('restaurant.orders.pay');
Route::get('/restaurant/orders/payment/callback', [RestaurantOrderPaymentController::class, 'callback'])->name('restaurant.orders.payment.callback');

Route::get(
    '/restaurant/reserve',
    [RestaurantReservationController::class, 'create']
)->name('restaurant.reserve');

Route::get(
    '/restaurant/reservations/{reservation}/print',
    function (RestaurantReservation $reservation) {
        abort_unless(
            auth()->user()?->hasAnyRole(['admin', 'receptionist']),
            403
        );

        return view('restaurant.print', compact('reservation'));
    }
)->middleware('auth')->name('restaurant.reservations.print');

Route::post(
    '/restaurant/reserve',
    [RestaurantReservationController::class, 'store']
)->name('restaurant.reserve.store');

Route::middleware('auth')->group(function () {
    Route::get(
        '/restaurant/reservations/{reservation}',
        [RestaurantReservationController::class, 'show']
    )->name('restaurant.reservations.show');

    Route::patch(
        '/restaurant/reservations/{reservation}/cancel',
        [RestaurantReservationController::class, 'cancel']
    )->name('restaurant.reservations.cancel');
});

$canAccessRestaurantReservation = static function (RestaurantReservation $reservation, ?string $token): bool {
    if ($reservation->guest_id === auth()->user()?->guest?->id) {
        return true;
    }

    return filled($token)
        && filled($reservation->access_token)
        && hash_equals($reservation->access_token, $token);
};

Route::get(

    '/restaurant/reservations/{reservation}/payment',

    function (
        Request $request,

        RestaurantReservation $reservation

    ) use ($canAccessRestaurantReservation) {

        $accessToken = $request->query('token');

        abort_unless($canAccessRestaurantReservation($reservation, $accessToken), 403);

        if (

            $reservation->hold_status == 'expired'

        ) {

            abort(403);

        }

        if (

            $reservation->hold_until

            &&

            now()->greaterThan(

                $reservation->hold_until

            )

        ) {

            $reservation->update([

                'hold_status' => 'expired',

                'status' => 'cancelled',

                'payment_status' => 'cancelled',

            ]);

            abort(403);

        }

        return view(

            'restaurant.payment',

            compact('reservation', 'accessToken')

        );

    }

)->name('restaurant.payment');

Route::post(
    '/restaurant/reservations/{reservation}/pay',
    function (Request $request, RestaurantReservation $reservation) use ($canAccessRestaurantReservation) {

        $accessToken = $request->query('token');

        abort_unless($canAccessRestaurantReservation($reservation, $accessToken), 403);

        if ($reservation->payment_status === 'completed') {

            return back()->with(
                'error',
                'Reservation has already been paid.'
            );
        }

        if ($reservation->hold_status === 'expired') {

            return back()->with(
                'error',
                'Reservation has expired.'
            );
        }

        if ($reservation->hold_until?->isPast()) {
            $reservation->update(['hold_status' => 'expired', 'status' => 'cancelled', 'payment_status' => 'cancelled']);

            return back()->with('error', 'Reservation has expired.');
        }

        $reference = 'REST-'.Str::upper(Str::random(16));
        $reservation->update(['transaction_reference' => $reference]);

        $response = Http::withToken(
            config('services.paystack.secretKey')
        )
            ->post(
                'https://api.paystack.co/transaction/initialize',
                [

                    'email' => $reservation->guest_email,

                    'amount' => $reservation->reservation_fee * 100,

                    'reference' => $reference,

                    'metadata' => ['restaurant_reservation_id' => $reservation->id],

                    'callback_url' => route('restaurant.verify', [
                        'reservation' => $reservation,
                        'token' => $accessToken,
                    ]),

                ]
            )
            ->json();

        if (
            ! isset(
                $response['data']['authorization_url']
            )
        ) {

            return back()->with(
                'error',
                'Unable to initialize payment.'
            );

        }

        return redirect(
            $response['data']['authorization_url']
        );

    }
)->name('restaurant.pay');

Route::get(
    '/restaurant/reservations/{reservation}/verify',
    function (Request $request, RestaurantReservation $reservation) use ($canAccessRestaurantReservation) {

        $accessToken = $request->query('token');

        abort_unless($canAccessRestaurantReservation($reservation, $accessToken), 403);

        $reference = request('reference');

        if (! $reference) {

            return redirect()
                ->route(
                    'restaurant.payment',
                    [
                        'reservation' => $reservation,
                        'token' => $accessToken,
                    ]
                )
                ->with(
                    'error',
                    'Missing payment reference.'
                );
        }

        $response = Http::withToken(
            config('services.paystack.secretKey')
        )
            ->get(
                "https://api.paystack.co/transaction/verify/{$reference}"
            )
            ->json();

        if (! isset($response['data']) || $response['data']['status'] !== 'success'
            || $reference !== $reservation->transaction_reference
            || (int) $response['data']['amount'] !== (int) round($reservation->reservation_fee * 100)
            || (int) data_get($response, 'data.metadata.restaurant_reservation_id') !== $reservation->id) {

            return redirect()
                ->route(
                    'restaurant.payment',
                    [
                        'reservation' => $reservation,
                        'token' => $accessToken,
                    ]
                )
                ->with(
                    'error',
                    'Payment verification failed.'
                );
        }

        $paymentRecorded = DB::transaction(function () use ($reservation, $reference): bool {
            $reservation = RestaurantReservation::query()->lockForUpdate()->findOrFail($reservation->id);

            if ($reservation->payment_status === 'completed') {
                return false;
            }

            $reservation->update(['payment_status' => 'completed', 'status' => 'confirmed', 'hold_status' => 'confirmed', 'hold_until' => null]);
            $reservation->table()->update(['status' => 'reserved']);
            Payment::firstOrCreate(['transaction_reference' => $reference], [
                'restaurant_reservation_id' => $reservation->id,
                'guest_id' => $reservation->guest_id,
                'amount' => $reservation->reservation_fee,
                'method' => 'paystack',
                'payment_status' => 'completed',
            ]);

            return true;
        });

        activity()
            ->performedOn($reservation)
            ->causedBy(auth()->user())
            ->event('payment')
            ->log('Restaurant reservation paid.');

        Mail::to($reservation->guest_email)
            ->send(new RestaurantPaymentReceived($reservation));

        return redirect()
            ->route('restaurant.reserve')
            ->with(
                'success',
                'Restaurant reservation confirmed.'
            );

    }
)->name('restaurant.verify');


Route::post('/restaurant/orders/{order}/cancel', function (\App\Models\RestaurantOrder $order) {
    $ownsOrder = in_array($order->id, session('restaurant_order_ids', []), true)
        || (auth()->id() && $order->guest?->user_id === auth()->id());
    abort_unless($ownsOrder, 403);
    abort_unless($order->status === 'pending' && $order->payment_status !== 'completed', 422);

    $order->update(['status' => 'cancelled', 'cancelled_at' => now()]);

    return redirect()->route('restaurant.menu')->with('success', 'Food order cancelled.');
})->name('restaurant.orders.cancel');
