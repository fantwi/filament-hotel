<?php

namespace Tests\Feature;

use App\Filament\Admin\Widgets\AdminFinanceStats;
use App\Models\Payment;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class AdminFinanceStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_payment_stat_includes_pending_and_unpaid_payments_in_the_selected_period(): void
    {
        $this->payment('pending', '2026-08-10 09:00:00');
        $this->payment('unpaid', '2026-08-11 09:00:00');
        $this->payment('completed', '2026-08-11 10:00:00');
        $this->payment('refunded', '2026-08-11 11:00:00');
        $this->payment('unpaid', '2026-07-31 09:00:00');

        $stats = $this->statsFor('2026-08-10', '2026-08-12');

        self::assertSame('2', $stats['Pending Payments']->getValue());
    }

    private function payment(string $status, string $createdAt): void
    {
        $payment = Payment::query()->create([
            'amount' => 100,
            'method' => 'cash',
            'payment_status' => $status,
            'transaction_reference' => 'ADMIN-FINANCE-'.str()->upper(str()->random(12)),
        ]);

        $payment->forceFill([
            'created_at' => Carbon::parse($createdAt),
            'updated_at' => Carbon::parse($createdAt),
        ])->saveQuietly();
    }

    /**
     * @return array<string, Stat>
     */
    private function statsFor(string $startDate, string $endDate): array
    {
        $widget = new AdminFinanceStats;
        $widget->pageFilters = [
            'period' => 'custom',
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        $method = new ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        return collect($method->invoke($widget))
            ->mapWithKeys(fn (Stat $stat): array => [(string) $stat->getLabel() => $stat])
            ->all();
    }
}
