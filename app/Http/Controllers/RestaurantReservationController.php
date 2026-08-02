<?php

namespace App\Http\Controllers;

use App\Mail\RestaurantReservationCreated;
use App\Models\Restaurant;
use App\Models\RestaurantReservation;
use App\Models\RestaurantTable;
use Carbon\Carbon;
use App\Services\CorporateCreditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class RestaurantReservationController extends Controller
{
    public function show(RestaurantReservation $reservation)
    {
        $this->authorizeGuestAccess($reservation);

        $reservation->load(['restaurant', 'table']);

        return view('restaurant.reservation-show', compact('reservation'));
    }

    public function cancel(RestaurantReservation $reservation)
    {
        $this->authorizeGuestAccess($reservation);

        if ($reservation->status !== 'pending' || $reservation->payment_status === 'completed') {
            return back()->with('error', 'Only unpaid pending reservations can be cancelled.');
        }

        $reservation->update([
            'status' => 'cancelled',
            'payment_status' => 'cancelled',
            'hold_status' => 'expired',
        ]);

        activity()
            ->performedOn($reservation)
            ->causedBy(auth()->user())
            ->event('cancelled')
            ->log('Restaurant reservation cancelled.');

        return redirect()
            ->route('restaurant.reserve')
            ->with('success', 'Restaurant reservation cancelled. The table hold has been released.');
    }

    public function create()
    {
        $restaurant = Restaurant::published()->first();

        $tables = RestaurantTable::query()
            ->whereNotIn('status', ['maintenance', 'cleaning'])
            ->orderBy('table_number')
            ->get();

        return view(
            'restaurant.reserve',
            compact('restaurant', 'tables')
        );
    }

    public function store(Request $request)
    {
        $restaurant = Restaurant::published()->firstOrFail();

        $validated = $request->validate([
            'restaurant_table_id' => ['required', 'exists:restaurant_tables,id'],
            'guest_name' => ['required', 'max:255'],
            'guest_email' => ['required', 'email'],
            'guest_phone' => ['required', 'max:50'],
            'reservation_date' => ['required', 'date', 'after_or_equal:today'],
            'reservation_time' => ['required', 'date_format:H:i'],
            'number_of_guests' => ['required', 'integer', 'min:1'],
            'special_requests' => ['nullable', 'string', 'max:2000'],
            'promotion_code' => ['nullable', 'string', 'max:100'],
        ]);

        $table = RestaurantTable::query()
            ->whereKey($validated['restaurant_table_id'])
            ->where('restaurant_id', $restaurant->id)
            ->whereNotIn('status', ['maintenance', 'cleaning'])
            ->first();

        if (! $table) {
            return back()->withInput()->withErrors([
                'restaurant_table_id' => 'This table is unavailable for reservations.',
            ]);
        }

        if ($validated['number_of_guests'] > $table->capacity) {
            return back()->withInput()->withErrors([
                'number_of_guests' => "This table seats up to {$table->capacity} guests.",
            ]);
        }

        $reservationStart = Carbon::parse(
            $validated['reservation_date'].' '.$validated['reservation_time']
        );

        $reservationEnd = $reservationStart
            ->copy()
            ->addMinutes(120);

        $existingReservation = RestaurantReservation::where(
            'restaurant_table_id',
            $validated['restaurant_table_id']
        )
            ->whereDate(
                'reservation_date',
                $validated['reservation_date']
            )
            ->where(function ($query) {
                $query
                    ->whereIn('status', [
                        'confirmed',
                        'checked_in',
                    ])
                    ->orWhere(function ($query) {
                        $query
                            ->where('status', 'pending')
                            ->where('hold_until', '>', now());
                    });
            })
            // ->whereIn('status', [
            //     'pending',
            //     'confirmed',
            //     'checked_in',
            // ])
            ->get()
            ->first(function ($reservation) use ($reservationStart, $reservationEnd) {

                $existingStart = Carbon::parse(
                    $reservation->reservation_date->format('Y-m-d').
                    ' '.
                    $reservation->reservation_time
                );

                $existingEnd = $existingStart
                    ->copy()
                    ->addMinutes($reservation->duration_minutes);

                return $reservationStart < $existingEnd
                    && $reservationEnd > $existingStart;
            });

        if ($existingReservation) {

            return back()

                ->withInput()

                ->withErrors([

                    'restaurant_table_id' => 'Sorry, this table is already reserved for the selected date and time.',

                ]);

        }

        $organization = app(CorporateCreditService::class)->organizationFor(auth()->user());
        $promotion = filled($validated['promotion_code'] ?? null) ? \App\Models\Promotion::query()->where('code', strtoupper($validated['promotion_code']))->applicable((float) $table->reservation_fee)->first() : null;
        if (filled($validated['promotion_code'] ?? null) && ! $promotion) return back()->withInput()->withErrors(['promotion_code' => 'This promotion code is not valid for this reservation.']);
        $reservationFee = app(\App\Services\BillingService::class)->calculate((float) $table->reservation_fee, $promotion?->discount_type, (float) ($promotion?->discount_value ?? 0))['total'];

        $reservation = RestaurantReservation::create([

            'restaurant_id' => $restaurant->id,

            'restaurant_table_id' => $table->id,

            'guest_id' => auth()->user()?->guest?->id,
            'corporate_organization_id' => $organization?->id,

            'guest_name' => $validated['guest_name'],

            'guest_email' => $validated['guest_email'],

            'guest_phone' => $validated['guest_phone'],

            'reservation_date' => $validated['reservation_date'],

            'reservation_time' => $validated['reservation_time'],

            'number_of_guests' => $validated['number_of_guests'],

            'special_requests' => $validated['special_requests'] ?? null,

            'duration_minutes' => 120,

            'status' => $organization ? 'confirmed' : 'pending',

            'payment_status' => 'pending',

            'hold_status' => $organization ? 'confirmed' : 'held',

            'hold_until' => $organization ? null : now()->addMinutes(15),

            'access_token' => Str::random(64),

        ]);

        activity()
            ->performedOn($reservation)
            ->causedBy(auth()->user())
            ->event('created')
            ->log('Restaurant reservation created.');

        Mail::to($reservation->guest_email)
            ->send(new RestaurantReservationCreated($reservation));

        if ($organization) {
            return redirect()->route('dashboard')
                ->with('success', "Your reservation has been confirmed and billed to {$organization->name}.");
        }

        return redirect()
            ->route('restaurant.payment', [
                'reservation' => $reservation,
                'token' => $reservation->access_token,
            ])
            ->with('success', 'Your reservation has been received.');
    }

    private function authorizeGuestAccess(RestaurantReservation $reservation): void
    {
        $user = auth()->user();

        abort_unless(
            $user && (
                $user->hasAnyRole(['admin', 'receptionist', 'manager'])
                || $reservation->guest_id === $user->guest?->id
            ),
            403
        );
    }
}
