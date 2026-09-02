<?php

namespace Tests\Feature;

use App\Filament\Admin\Widgets\RecentPayments;
use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\RestaurantOrder;
use App\Models\RestaurantReservation;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Tests\TestCase;

class RecentPaymentsWidgetTest extends TestCase
{
    public function test_recent_payments_display_guests_from_every_supported_transaction(): void
    {
        $guest = new Guest([
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'email' => 'ama@example.test',
        ]);
        $guest->id = 42;

        $directPayment = new Payment(['guest_id' => $guest->id]);
        $directPayment->setRelation('guest', $guest);

        $cases = [$directPayment];

        foreach ([
            [new Payment(['booking_id' => 11]), new Booking(['guest_id' => $guest->id]), 'booking'],
            [new Payment(['conference_booking_id' => 12]), new ConferenceBooking(['guest_id' => $guest->id]), 'conferenceBooking'],
            [new Payment(['restaurant_reservation_id' => 13]), new RestaurantReservation(['guest_id' => $guest->id]), 'restaurantReservation'],
            [new Payment(['restaurant_order_id' => 14]), new RestaurantOrder(['guest_id' => $guest->id]), 'restaurantOrder'],
        ] as [$payment, $transaction, $relation]) {
            $transaction->setRelation('guest', $guest);
            $payment->setRelation($relation, $transaction);
            $cases[] = $payment;
        }

        $column = $this->table()->getColumn('transaction_guest');

        self::assertInstanceOf(TextColumn::class, $column);

        foreach ($cases as $payment) {
            $column->record($payment)->clearCachedState();

            self::assertSame('Ama Mensah', $column->getState());
            self::assertSame('ama@example.test', $column->getDescriptionBelow());
        }

        $column->record(new Payment)->clearCachedState();

        self::assertSame('Guest not recorded', $column->getState());
        self::assertNull($column->getDescriptionBelow());
    }

    public function test_recent_payments_eager_load_every_supported_guest_path(): void
    {
        $eagerLoads = array_keys($this->table()->getQuery()->getEagerLoads());

        foreach ([
            'guest',
            'booking.guest',
            'conferenceBooking.guest',
            'restaurantReservation.guest',
            'restaurantOrder.guest',
        ] as $relationship) {
            self::assertContains($relationship, $eagerLoads);
        }
    }

    private function table(): Table
    {
        $widget = new RecentPayments;

        return $widget->table(Table::make($this->createMock(HasTable::class)));
    }
}
