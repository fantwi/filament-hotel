<?php

namespace Tests\Feature;

use App\Filament\Admin\Widgets\TransactionOverview;
use App\Filament\Admin\Widgets\TransactionStats;
use App\Models\Booking;
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
            'outstanding' => 375.0,
            'outstanding_count' => 1,
            'corporate_outstanding' => 0.0,
            'corporate_outstanding_count' => 0,
        ], $rows['Hotel bookings']);
        self::assertSame([
            'transactions' => 1,
            'gross' => 500.0,
            'payments' => 125.0,
            'payment_count' => 1,
            'outstanding' => 375.0,
            'outstanding_count' => 1,
            'corporate_outstanding' => 0.0,
            'corporate_outstanding_count' => 0,
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
