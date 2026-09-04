<?php

namespace Tests\Feature;

use App\Filament\Admin\Widgets\TransactionOverview;
use App\Filament\Admin\Widgets\TransactionStats;
use App\Models\Booking;
use App\Models\CorporateOrganization;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomType;
use App\Services\TransactionDashboardSummary;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\TestCase;

class TransactionDashboardSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_summary_returns_channel_rows_and_totals_for_the_selected_period(): void
    {
        [$guest, $room] = $this->hotelFixture();
        $booking = $this->createdAt(Booking::query()->create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-11',
            'total_price' => 500,
            'status' => 'confirmed',
            'payment_status' => 'pending',
        ]), '2026-08-10 09:00:00');
        $this->createdAt(Payment::query()->create([
            'guest_id' => $guest->id,
            'booking_id' => $booking->id,
            'amount' => 125,
            'method' => 'cash',
            'payment_status' => 'completed',
            'transaction_reference' => 'SHARED-SUMMARY-PAID',
        ]), '2026-08-11 09:00:00');

        $summary = app(TransactionDashboardSummary::class)->summarize(
            Carbon::parse('2026-08-01')->startOfDay(),
            Carbon::parse('2026-08-31')->endOfDay(),
        );
        $rows = collect($summary['rows'])->keyBy('label');

        self::assertSame([
            'Hotel bookings',
            'Conference bookings',
            'Table reservations',
            'Food orders',
        ], $rows->keys()->all());
        self::assertSame([
            'label' => 'Hotel bookings',
            'transactions' => 1,
            'gross' => 500.0,
            'payments' => 125.0,
            'payment_count' => 1,
            'refunds' => 0.0,
            'refund_count' => 0,
            'cohort_collections' => 125.0,
            'outstanding' => 375.0,
            'outstanding_count' => 1,
            'non_corporate_outstanding' => 375.0,
            'non_corporate_outstanding_count' => 1,
            'corporate_outstanding' => 0.0,
            'corporate_outstanding_count' => 0,
            'overdue_corporate_outstanding' => 0.0,
            'overdue_corporate_outstanding_count' => 0,
        ], $rows['Hotel bookings']);
        self::assertSame([
            'transactions' => 1,
            'gross' => 500.0,
            'payments' => 125.0,
            'payment_count' => 1,
            'refunds' => 0.0,
            'refund_count' => 0,
            'net_collections' => 125.0,
            'cohort_collections' => 125.0,
            'collection_rate' => 25.0,
            'outstanding' => 375.0,
            'outstanding_count' => 1,
            'non_corporate_outstanding' => 375.0,
            'non_corporate_outstanding_count' => 1,
            'corporate_outstanding' => 0.0,
            'corporate_outstanding_count' => 0,
            'overdue_corporate_outstanding' => 0.0,
            'overdue_corporate_outstanding_count' => 0,
        ], $summary['totals']);
    }

    public function test_shared_summary_uses_two_aggregate_queries_per_transaction_channel(): void
    {
        $queryCount = $this->countQueries(fn (): array => app(TransactionDashboardSummary::class)->summarize(
            Carbon::parse('2026-08-01')->startOfDay(),
            Carbon::parse('2026-08-31')->endOfDay(),
        ));

        self::assertSame(8, $queryCount);
    }

    public function test_financial_indicators_use_payment_dates_refund_dates_and_corporate_terms(): void
    {
        Carbon::setTestNow('2026-09-02 12:00:00');

        try {
            [$guest, $room] = $this->hotelFixture();
            $overdueOrganization = CorporateOrganization::query()->create([
                'name' => 'Overdue Corporate Account',
                'payment_terms_days' => 15,
                'is_credit_enabled' => true,
            ]);
            $currentOrganization = CorporateOrganization::query()->create([
                'name' => 'Current Corporate Account',
                'payment_terms_days' => 60,
                'is_credit_enabled' => true,
            ]);

            $personalBooking = $this->booking($guest, $room, 1000, '2026-08-01 09:00:00');
            $overdueCorporateBooking = $this->booking(
                $guest,
                $room,
                2000,
                '2026-08-01 10:00:00',
                $overdueOrganization,
            );
            $currentCorporateBooking = $this->booking(
                $guest,
                $room,
                1000,
                '2026-08-15 10:00:00',
                $currentOrganization,
            );

            $this->payment($guest, $personalBooking, 400, 'INDICATOR-COLLECTED-IN-PERIOD', 'completed', '2026-08-10', '2026-08-10');
            $this->payment($guest, $overdueCorporateBooking, 500, 'INDICATOR-COLLECTED-BEFORE-PERIOD', 'completed', '2026-07-20', '2026-07-20');
            $this->payment($guest, $personalBooking, 100, 'INDICATOR-REFUNDED-IN-PERIOD', 'refunded', '2026-07-15', '2026-09-10', '2026-08-20');
            $this->payment($guest, $currentCorporateBooking, 50, 'INDICATOR-REFUNDED-AFTER-PERIOD', 'refunded', '2026-08-20', '2026-08-25', '2026-09-01');

            $summary = app(TransactionDashboardSummary::class)->summarize(
                Carbon::parse('2026-08-01')->startOfDay(),
                Carbon::parse('2026-08-31')->endOfDay(),
            );

            self::assertSame(400.0, $summary['totals']['payments']);
            self::assertSame(100.0, $summary['totals']['refunds'] ?? null);
            self::assertSame(1, $summary['totals']['refund_count'] ?? null);
            self::assertSame(300.0, $summary['totals']['net_collections'] ?? null);
            self::assertSame(900.0, $summary['totals']['cohort_collections'] ?? null);
            self::assertSame(22.5, $summary['totals']['collection_rate'] ?? null);
            self::assertSame(600.0, $summary['totals']['non_corporate_outstanding'] ?? null);
            self::assertSame(1, $summary['totals']['non_corporate_outstanding_count'] ?? null);
            self::assertSame(2500.0, $summary['totals']['corporate_outstanding']);
            self::assertSame(2, $summary['totals']['corporate_outstanding_count']);
            self::assertSame(1500.0, $summary['totals']['overdue_corporate_outstanding'] ?? null);
            self::assertSame(1, $summary['totals']['overdue_corporate_outstanding_count'] ?? null);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_empty_period_has_a_zero_collection_rate(): void
    {
        $summary = app(TransactionDashboardSummary::class)->summarize(
            Carbon::parse('2026-08-01')->startOfDay(),
            Carbon::parse('2026-08-31')->endOfDay(),
        );

        self::assertSame(0.0, $summary['totals']['collection_rate'] ?? null);
    }

    public function test_both_dashboard_widgets_stay_within_the_combined_query_budget(): void
    {
        $filters = [
            'period' => 'custom',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
        ];
        $stats = new TransactionStats;
        $stats->pageFilters = $filters;
        $overview = new TransactionOverview;
        $overview->pageFilters = $filters;

        $queryCount = $this->countQueries(function () use ($stats, $overview): void {
            $this->invokeProtected($stats, 'getStats');
            $this->invokeProtected($overview, 'getViewData');
        });

        self::assertSame(16, $queryCount);
    }

    /**
     * @return array{0: Guest, 1: Room}
     */
    private function hotelFixture(): array
    {
        $guest = Guest::query()->create([
            'first_name' => 'Shared',
            'last_name' => 'Summary Guest',
            'email' => 'shared-summary@example.test',
            'phone_number' => '0240000000',
        ]);
        $roomType = RoomType::query()->create([
            'name' => 'Shared Summary Room Type',
            'price_per_night' => 500,
            'capacity' => 2,
        ]);
        $room = Room::query()->create([
            'room_type_id' => $roomType->id,
            'room_number' => 'SHARED-SUMMARY-101',
            'status' => 'available',
        ]);

        return [$guest, $room];
    }

    private function booking(
        Guest $guest,
        Room $room,
        float $amount,
        string $createdAt,
        ?CorporateOrganization $organization = null,
    ): Booking {
        return $this->createdAt(Booking::query()->create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'corporate_organization_id' => $organization?->id,
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-11',
            'total_price' => $amount,
            'status' => 'confirmed',
            'payment_status' => 'pending',
        ]), $createdAt);
    }

    private function payment(
        Guest $guest,
        Booking $booking,
        float $amount,
        string $reference,
        string $status,
        string $createdAt,
        string $updatedAt,
        ?string $refundedAt = null,
    ): Payment {
        $payment = Payment::query()->create([
            'guest_id' => $guest->id,
            'booking_id' => $booking->id,
            'amount' => $amount,
            'method' => 'cash',
            'payment_status' => $status,
            'transaction_reference' => $reference,
        ]);
        $payment->forceFill([
            'created_at' => Carbon::parse($createdAt),
            'updated_at' => Carbon::parse($updatedAt),
            'refunded_at' => $refundedAt ? Carbon::parse($refundedAt) : null,
        ])->saveQuietly();

        return $payment;
    }

    /**
     * @template TModel of Model
     *
     * @param  TModel  $model
     * @return TModel
     */
    private function createdAt(Model $model, string $createdAt): Model
    {
        $model->forceFill([
            'created_at' => Carbon::parse($createdAt),
            'updated_at' => Carbon::parse($createdAt),
        ])->saveQuietly();

        return $model;
    }

    private function countQueries(callable $callback): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $callback();

            return count(DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
        }
    }

    private function invokeProtected(object $target, string $methodName): mixed
    {
        $method = new ReflectionMethod($target, $methodName);
        $method->setAccessible(true);

        return $method->invoke($target);
    }
}
