<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\GuestReport;
use App\Models\Guest;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestReportCalculationRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_collection_scope_includes_supported_statuses_at_inclusive_period_boundaries(): void
    {
        $firstGuest = $this->guest('First', 'first@example.test');
        $secondGuest = $this->guest('Second', 'second@example.test');

        $this->payment($firstGuest, 100, 'completed', '2026-09-01 00:00:00', 'BOUNDARY-START');
        $this->payment($firstGuest, 50, 'paid', '2026-09-02 23:59:59', 'BOUNDARY-END');
        $this->payment($secondGuest, 300, 'refunded', '2026-09-02 12:00:00', 'REFUNDED-IN-PERIOD', '2026-09-02 13:00:00');

        $this->payment($firstGuest, 900, 'pending', '2026-09-02 10:00:00', 'PENDING-EXCLUDED');
        $this->payment($firstGuest, 800, 'unpaid', '2026-09-02 10:30:00', 'UNPAID-EXCLUDED');
        $this->payment($secondGuest, 700, 'failed', '2026-09-02 11:00:00', 'FAILED-EXCLUDED');
        $this->payment($firstGuest, 1000, 'completed', '2026-08-31 23:59:59', 'BEFORE-PERIOD');
        $this->payment($secondGuest, 1100, 'completed', '2026-09-03 00:00:00', 'AFTER-PERIOD');

        $report = $this->reportFor('2026-09-01', '2026-09-02');

        self::assertSame(2, $report['payingGuests']);
        self::assertSame(3, $report['paymentCount']);
        self::assertSame(450.0, $report['totalPaid']);
        self::assertSame(300.0, $report['refundTotal']);
        self::assertSame(1, $report['refundCount']);
        self::assertSame(150.0, $report['netSpend']);
        self::assertSame([
            'hotel' => 0,
            'conference' => 0,
            'table' => 0,
            'food' => 0,
            'other' => 3,
        ], $report['activity']);
    }

    public function test_average_spend_uses_distinct_paying_guests_instead_of_payment_count(): void
    {
        $firstGuest = $this->guest('First', 'first-average@example.test');
        $secondGuest = $this->guest('Second', 'second-average@example.test');

        $this->payment($firstGuest, 100, 'completed', '2026-09-01 09:00:00', 'AVERAGE-FIRST');
        $this->payment($firstGuest, 50, 'completed', '2026-09-01 10:00:00', 'AVERAGE-SECOND');
        $this->payment($secondGuest, 300, 'completed', '2026-09-01 11:00:00', 'AVERAGE-THIRD');

        $report = $this->reportFor('2026-09-01', '2026-09-01');

        self::assertSame(2, $report['payingGuests']);
        self::assertSame(3, $report['paymentCount']);
        self::assertSame(450.0, $report['totalPaid']);
        self::assertSame(225.0, $report['averageSpend']);
    }

    public function test_top_guest_leaderboard_is_sorted_by_gross_revenue_and_limited_to_five(): void
    {
        $guests = [];

        foreach (range(1, 7) as $rank) {
            $guest = $this->guest('Guest'.$rank, "guest{$rank}@example.test");
            $guests[$rank] = $guest;
            $this->payment($guest, $rank * 10, 'completed', '2026-09-02 12:00:00', "LEADERBOARD-{$rank}");
        }

        $topGuests = $this->reportFor('2026-09-01', '2026-09-03')['topGuests'];

        self::assertCount(5, $topGuests);
        self::assertSame(
            [$guests[7]->id, $guests[6]->id, $guests[5]->id, $guests[4]->id, $guests[3]->id],
            $topGuests->pluck('guest.id')->all(),
        );
        self::assertSame(
            [70.0, 60.0, 50.0, 40.0, 30.0],
            $topGuests->pluck('total_spend')->map(fn ($amount): float => (float) $amount)->all(),
        );
    }

    private function guest(string $firstName, string $email): Guest
    {
        $guest = Guest::query()->create([
            'first_name' => $firstName,
            'last_name' => 'Guest',
            'email' => $email,
            'phone_number' => '0240000000',
        ]);

        $guest->forceFill([
            'created_at' => Carbon::parse('2026-08-01 12:00:00'),
            'updated_at' => Carbon::parse('2026-08-01 12:00:00'),
        ])->saveQuietly();

        return $guest;
    }

    private function payment(
        Guest $guest,
        float $amount,
        string $status,
        string $createdAt,
        string $reference,
        ?string $refundedAt = null,
    ): Payment {
        $payment = Payment::query()->create([
            'guest_id' => $guest->id,
            'amount' => $amount,
            'method' => 'cash',
            'payment_status' => $status,
            'transaction_reference' => $reference,
        ]);

        $payment->forceFill([
            'created_at' => Carbon::parse($createdAt),
            'updated_at' => Carbon::parse($createdAt),
            'refunded_at' => $refundedAt === null ? null : Carbon::parse($refundedAt),
        ])->saveQuietly();

        return $payment;
    }

    /**
     * @return array<string, mixed>
     */
    private function reportFor(string $startDate, string $endDate): array
    {
        $page = new GuestReport;
        $page->period = 'custom';
        $page->startDate = $startDate;
        $page->endDate = $endDate;

        return $page->report();
    }
}
