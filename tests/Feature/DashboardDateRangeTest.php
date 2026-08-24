<?php

namespace Tests\Feature;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Filament\Admin\Pages\Dashboards\AccountantDashboard;
use App\Filament\Admin\Pages\Dashboards\AdminDashboard;
use App\Filament\Admin\Pages\Dashboards\ManagerDashboard;
use App\Filament\Admin\Pages\Dashboards\ReceptionDashboard;
use App\Filament\Admin\Pages\Dashboards\SuperAdminDashboard;
use App\Filament\Admin\Pages\Dashboards\TimeFilteredDashboard;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardDateRangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_role_dashboards_use_the_shared_time_filter(): void
    {
        foreach ([
            AdminDashboard::class,
            SuperAdminDashboard::class,
            AccountantDashboard::class,
            ManagerDashboard::class,
            ReceptionDashboard::class,
        ] as $dashboard) {
            self::assertTrue(is_subclass_of($dashboard, TimeFilteredDashboard::class));
        }
    }

    public function test_dashboard_date_range_filters_queries_to_the_selected_dates(): void
    {
        $inside = Payment::create([
            'amount' => 120,
            'method' => 'cash',
            'payment_status' => 'completed',
            'transaction_reference' => 'DASHBOARD-IN-RANGE',
        ]);
        $inside->forceFill(['created_at' => Carbon::parse('2026-08-05 12:00:00')])->saveQuietly();

        $outside = Payment::create([
            'amount' => 80,
            'method' => 'cash',
            'payment_status' => 'completed',
            'transaction_reference' => 'DASHBOARD-OUT-OF-RANGE',
        ]);
        $outside->forceFill(['created_at' => Carbon::parse('2026-07-30 12:00:00')])->saveQuietly();

        $probe = new DashboardDateRangeProbe;
        $probe->pageFilters = [
            'period' => 'weekly',
            'start_date' => '2026-08-04',
            'end_date' => '2026-08-06',
        ];

        self::assertSame(1, $probe->filter(Payment::query())->count());
        self::assertSame('Aug 4, 2026 - Aug 6, 2026', $probe->label());
    }

    public function test_time_breakdown_presets_cover_every_requested_period(): void
    {
        foreach (['daily', 'weekly', 'monthly', 'quarterly', 'yearly'] as $period) {
            [$start, $end] = TimeFilteredDashboard::presetRange($period);

            self::assertTrue($start->lessThanOrEqualTo($end), $period);
            self::assertTrue($end->isToday(), $period);
        }
    }
}

class DashboardDateRangeProbe
{
    use InteractsWithDashboardDateRange;

    public function filter(Builder $query): Builder
    {
        return $this->forDashboardDateRange($query);
    }

    public function label(): string
    {
        return $this->dashboardDateRangeLabel();
    }
}
