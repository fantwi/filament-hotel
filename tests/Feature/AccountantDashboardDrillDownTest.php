<?php

namespace Tests\Feature;

use App\Filament\Admin\Widgets\AccountantReceivablesStats;
use App\Filament\Admin\Widgets\AccountantStats;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class AccountantDashboardDrillDownTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_cash_position_stats_link_to_the_matching_payment_and_transaction_scopes(): void
    {
        $stats = $this->stats(new AccountantStats);

        $this->assertPaymentLink($stats['Completed Revenue'], 'collected');
        $this->assertTransactionDashboardLink($stats['Outstanding Balance']);
        $this->assertPaymentLink($stats['Refunded'], 'refunded');
        $this->assertPaymentLink($stats['Payments Received'], 'collected');
    }

    public function test_receivable_stats_link_to_transaction_analysis_and_corporate_settlement(): void
    {
        $stats = $this->stats(new AccountantReceivablesStats);

        foreach ([
            'Hotel booking receivables',
            'Conference receivables',
            'Table-reservation receivables',
            'Food-order receivables',
        ] as $label) {
            $this->assertTransactionDashboardLink($stats[$label]);
        }

        $this->assertLink($stats['Corporate-billed receivables'], '/admin/corporate-receivables');
    }

    /**
     * @return array<string, Stat>
     */
    private function stats(object $widget): array
    {
        $widget->pageFilters = [
            'period' => 'monthly',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-15',
        ];

        $method = new ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        return collect($method->invoke($widget))
            ->mapWithKeys(fn (Stat $stat): array => [(string) $stat->getLabel() => $stat])
            ->all();
    }

    private function assertPaymentLink(Stat $stat, string $status): void
    {
        $query = $this->assertLink($stat, '/admin/payments');

        self::assertSame('all', data_get($query, 'filters.transaction_type'));
        self::assertSame($status, data_get($query, 'filters.payment_status'));
        $this->assertSelectedPeriod($query);
    }

    private function assertTransactionDashboardLink(Stat $stat): void
    {
        $query = $this->assertLink($stat, '/admin/transaction-dashboard');

        $this->assertSelectedPeriod($query);
    }

    /**
     * @return array<string, mixed>
     */
    private function assertLink(Stat $stat, string $path): array
    {
        $url = (string) $stat->getUrl();
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        self::assertSame($path, parse_url($url, PHP_URL_PATH));
        self::assertSame('heroicon-m-arrow-top-right-on-square', $stat->getDescriptionIcon());

        return $query;
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function assertSelectedPeriod(array $query): void
    {
        self::assertSame('monthly', data_get($query, 'filters.period'));
        self::assertSame('2026-08-01', data_get($query, 'filters.start_date'));
        self::assertSame('2026-08-15', data_get($query, 'filters.end_date'));
    }
}
