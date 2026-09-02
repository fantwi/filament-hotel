<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\RestaurantOrders\Pages\ListRestaurantOrders;
use App\Filament\Admin\Widgets\SuperAdminFinanceStats;
use App\Filament\Admin\Widgets\SuperAdminOperationsStats;
use App\Filament\Admin\Widgets\SuperAdminStats;
use App\Models\Payment;
use App\Models\RestaurantOrder;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use ReflectionMethod;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SuperAdminDashboardDrillDownTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_actionable_super_admin_stats_link_to_matching_date_filtered_lists(): void
    {
        $filters = [
            'period' => 'monthly',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-15',
        ];

        $summary = $this->stats(new SuperAdminStats, $filters);
        $operations = $this->stats(new SuperAdminOperationsStats, $filters);
        $finance = $this->stats(new SuperAdminFinanceStats, $filters);

        $this->assertFoodOrderLink($summary['Restaurant Orders'], null);
        $this->assertFoodOrderLink($operations['Kitchen Queue'], 'kitchen_queue');
        $this->assertPaymentLink($summary['Total Revenue'], 'collected');
        $this->assertPaymentLink($finance['Pending Payments'], 'pending');
        $this->assertPaymentLink($finance['Refunds'], 'refunded');

        self::assertNull($summary['Cancellation Rate']->getUrl());
    }

    public function test_pending_payment_and_kitchen_queue_values_match_their_destination_scopes(): void
    {
        $this->travelTo('2026-08-10 12:00:00');

        $this->payment('pending', 'PENDING-STAT');
        $this->payment('unpaid', 'UNPAID-STAT');
        $this->payment('completed', 'COMPLETED-STAT');

        $eligible = $this->order('confirmed', 'completed', null, 'ORDER-ELIGIBLE');
        $corporate = $this->order('preparing', 'pending', 'corporate_account', 'ORDER-CORPORATE');
        $unpaid = $this->order('confirmed', 'pending', null, 'ORDER-UNPAID');
        $served = $this->order('served', 'completed', null, 'ORDER-SERVED');

        $filters = [
            'period' => 'monthly',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-15',
        ];

        self::assertSame('2', $this->stats(new SuperAdminFinanceStats, $filters)['Pending Payments']->getValue());
        self::assertSame('2', $this->stats(new SuperAdminOperationsStats, $filters)['Kitchen Queue']->getValue());

        Permission::findOrCreate('manage kitchen orders', 'web');
        $admin = User::factory()->create(['department' => 'admin']);
        $admin->givePermissionTo('manage kitchen orders');

        Livewire::withQueryParams([
            'filters' => [
                'status' => ['value' => 'kitchen_queue'],
                'created_at' => [
                    'created_from' => '2026-08-01',
                    'created_until' => '2026-08-15',
                ],
            ],
        ])->actingAs($admin)
            ->test(ListRestaurantOrders::class)
            ->assertCanSeeTableRecords([$eligible, $corporate])
            ->assertCanNotSeeTableRecords([$unpaid, $served]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, Stat>
     */
    private function stats(object $widget, array $filters): array
    {
        $widget->pageFilters = $filters;
        $method = new ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        return collect($method->invoke($widget))
            ->mapWithKeys(fn (Stat $stat): array => [(string) $stat->getLabel() => $stat])
            ->all();
    }

    private function assertFoodOrderLink(Stat $stat, ?string $status): void
    {
        $query = $this->urlQuery($stat);

        self::assertSame('/admin/restaurant-orders', parse_url((string) $stat->getUrl(), PHP_URL_PATH));
        self::assertArrayHasKey('filters', $query);
        self::assertSame('2026-08-01', $query['filters']['created_at']['created_from']);
        self::assertSame('2026-08-15', $query['filters']['created_at']['created_until']);
        self::assertSame($status, $query['filters']['status']['value'] ?? null);
        self::assertSame('heroicon-m-arrow-top-right-on-square', $stat->getDescriptionIcon());
    }

    private function assertPaymentLink(Stat $stat, string $status): void
    {
        $query = $this->urlQuery($stat);

        self::assertSame('/admin/payments', parse_url((string) $stat->getUrl(), PHP_URL_PATH));
        self::assertSame('monthly', $query['filters']['period']);
        self::assertSame('2026-08-01', $query['filters']['start_date']);
        self::assertSame('2026-08-15', $query['filters']['end_date']);
        self::assertSame($status, $query['filters']['payment_status']);
        self::assertSame('heroicon-m-arrow-top-right-on-square', $stat->getDescriptionIcon());
    }

    /** @return array<string, mixed> */
    private function urlQuery(Stat $stat): array
    {
        parse_str((string) parse_url((string) $stat->getUrl(), PHP_URL_QUERY), $query);

        return $query;
    }

    private function payment(string $status, string $reference): Payment
    {
        return Payment::query()->create([
            'amount' => 100,
            'method' => 'cash',
            'payment_status' => $status,
            'transaction_reference' => $reference,
        ]);
    }

    private function order(string $status, string $paymentStatus, ?string $paymentMethod, string $number): RestaurantOrder
    {
        return RestaurantOrder::query()->create([
            'order_number' => $number,
            'subtotal' => 100,
            'total' => 100,
            'status' => $status,
            'payment_status' => $paymentStatus,
            'payment_method' => $paymentMethod,
        ]);
    }
}
