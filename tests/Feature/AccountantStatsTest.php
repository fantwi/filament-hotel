<?php

namespace Tests\Feature;

use App\Filament\Admin\Widgets\AccountantStats;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountantStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_payments_received_counts_only_collected_payments_in_the_selected_range(): void
    {
        $this->payment('paid', 'PAID-001', '2026-09-01 09:00:00');
        $this->payment('completed', 'COMPLETED-001', '2026-09-02 09:00:00');
        $this->payment('pending', 'PENDING-001', '2026-09-02 10:00:00');
        $this->payment('unpaid', 'UNPAID-001', '2026-09-02 11:00:00');
        $this->payment('refunded', 'REFUNDED-001', '2026-09-02 12:00:00');
        $this->payment('paid', 'OUTSIDE-RANGE-001', '2026-08-31 23:59:59');

        $widget = new AccountantStats;
        $widget->pageFilters = [
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-02',
        ];

        $method = new \ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);
        $stats = collect($method->invoke($widget))
            ->mapWithKeys(fn (Stat $stat): array => [(string) $stat->getLabel() => $stat]);

        self::assertSame('2', $stats['Payments Received']->getValue());
    }

    private function payment(string $status, string $reference, string $createdAt): void
    {
        Payment::query()->forceCreate([
            'amount' => 100,
            'method' => 'cash',
            'payment_status' => $status,
            'transaction_reference' => $reference,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}
