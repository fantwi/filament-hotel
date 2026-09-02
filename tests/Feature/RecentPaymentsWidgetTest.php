<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Payments\PaymentResource;
use App\Filament\Admin\Widgets\RecentPayments;
use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\RestaurantOrder;
use App\Models\RestaurantReservation;
use Filament\Actions\Action;
use Filament\Facades\Filament;
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

    public function test_recent_payments_show_source_transaction_identity_on_mobile(): void
    {
        $column = $this->table()->getColumn('transaction_id');

        self::assertInstanceOf(TextColumn::class, $column);
        self::assertNull($column->getVisibleFrom());

        foreach ([
            [new Payment(['booking_id' => 11]), 'Hotel booking #11'],
            [new Payment(['conference_booking_id' => 12]), 'Conference booking #12'],
            [new Payment(['restaurant_reservation_id' => 13]), 'Table reservation #13'],
            [new Payment(['restaurant_order_id' => 14]), 'Food order #14'],
            [new Payment, 'Unlinked payment'],
        ] as [$payment, $expected]) {
            $column->record($payment)->clearCachedState();

            self::assertSame($expected, $column->getState());
        }
    }

    public function test_recent_payments_details_action_targets_the_selected_payment(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $payment = new Payment;
        $payment->id = 91;
        $payment->exists = true;
        $action = $this->table()->getAction('details');

        self::assertInstanceOf(Action::class, $action);

        $action->record($payment);

        self::assertSame('Details', $action->getLabel());
        self::assertSame('heroicon-o-eye', $action->getIcon());
        self::assertSame(
            parse_url(PaymentResource::getUrl('view', ['record' => $payment]), PHP_URL_PATH),
            parse_url((string) $action->getUrl(), PHP_URL_PATH),
        );
    }

    private function table(): Table
    {
        $widget = new RecentPayments;

        return $widget->table(Table::make($this->createMock(HasTable::class)));
    }
}
