<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\Payment;
use App\Models\RestaurantOrder;
use App\Models\RestaurantReservation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Encapsulates business rules for corporate payment service.
 */
class CorporatePaymentService
{
    /**
     * Record a settlement received outside the online checkout and synchronize the
     * underlying booking, reservation, or order with the guest-facing paid state.
     */
    public function recordOfflinePayment(Model $transaction, string $method, ?string $reference = null): Payment
    {
        if (! in_array($method, ['cash', 'momo', 'card', 'bank_transfer'], true)) {
            throw new InvalidArgumentException('Choose a supported offline payment method.');
        }

        return DB::transaction(function () use ($transaction, $method, $reference): Payment {
            $transaction = $transaction::query()->lockForUpdate()->findOrFail($transaction->getKey());

            if (! $transaction->corporate_organization_id) {
                throw new InvalidArgumentException('Only corporate-billed transactions can be cleared here.');
            }

            if ($this->isPaid($transaction)) {
                throw new InvalidArgumentException('This transaction has already been paid.');
            }

            $payment = Payment::create([
                ...$this->paymentLink($transaction),
                'guest_id' => $transaction->guest_id,
                'amount' => $this->amount($transaction),
                'method' => $method,
                'payment_status' => 'completed',
                'transaction_reference' => $reference ?: 'OFF-'.Str::upper(Str::random(16)),
            ]);

            $this->markTransactionPaid($transaction, $method);

            activity()
                ->performedOn($transaction)
                ->causedBy(auth()->user())
                ->event('payment')
                ->withProperties(['method' => $method, 'payment_id' => $payment->id])
                ->log('Corporate-billed transaction cleared by offline payment.');

            return $payment;
        });
    }

    /**
     * Determines whether this record is paid.
     */
    private function isPaid(Model $transaction): bool
    {
        return match ($transaction::class) {
            Booking::class, ConferenceBooking::class => $transaction->payment_status === 'paid',
            RestaurantReservation::class, RestaurantOrder::class => $transaction->payment_status === 'completed',
            default => false,
        };
    }

    /**
     * Returns the chargeable amount for the supplied transaction.
     */
    private function amount(Model $transaction): float
    {
        $gross = match ($transaction::class) {
            Booking::class, ConferenceBooking::class => (float) $transaction->total_price,
            RestaurantReservation::class => (float) $transaction->reservation_fee,
            RestaurantOrder::class => (float) $transaction->total,
            default => throw new InvalidArgumentException('Unsupported corporate transaction.'),
        };

        $paid = (float) $transaction->payments()
            ->whereIn('payment_status', ['paid', 'completed'])
            ->sum('amount');

        return max(0, $gross - $paid);
    }

    /**
     * Builds the guest-dashboard link used to settle an unpaid corporate transaction.
     */
    private function paymentLink(Model $transaction): array
    {
        return match ($transaction::class) {
            Booking::class => ['booking_id' => $transaction->id],
            ConferenceBooking::class => ['conference_booking_id' => $transaction->id],
            RestaurantReservation::class => ['restaurant_reservation_id' => $transaction->id],
            RestaurantOrder::class => ['restaurant_order_id' => $transaction->id],
            default => throw new InvalidArgumentException('Unsupported corporate transaction.'),
        };
    }

    /**
     * Marks a corporate transaction as paid and records the clearing payment.
     */
    private function markTransactionPaid(Model $transaction, string $method): void
    {
        match ($transaction::class) {
            Booking::class => $transaction->update([
                'payment_status' => 'paid',
                'status' => 'confirmed',
                'hold_status' => 'confirmed',
                'hold_until' => null,
            ]),
            ConferenceBooking::class => $transaction->update([
                'payment_status' => 'paid',
                'status' => 'confirmed',
                'hold_until' => null,
            ]),
            RestaurantReservation::class => $transaction->update([
                'payment_status' => 'completed',
                'status' => 'confirmed',
                'hold_status' => 'confirmed',
                'hold_until' => null,
            ]),
            RestaurantOrder::class => $transaction->update([
                'payment_status' => 'completed',
                'payment_method' => $method,
                'paid_at' => now(),
                'status' => 'confirmed',
                'confirmed_at' => $transaction->confirmed_at ?? now(),
            ]),
            default => throw new InvalidArgumentException('Unsupported corporate transaction.'),
        };
    }
}
