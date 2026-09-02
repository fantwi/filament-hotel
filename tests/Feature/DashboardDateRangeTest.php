<?php

namespace Tests\Feature;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Filament\Admin\Pages\Dashboards\AccountantDashboard;
use App\Filament\Admin\Pages\Dashboards\AdminDashboard;
use App\Filament\Admin\Pages\Dashboards\ManagerDashboard;
use App\Filament\Admin\Pages\Dashboards\ReceptionDashboard;
use App\Filament\Admin\Pages\Dashboards\SuperAdminDashboard;
use App\Filament\Admin\Pages\Dashboards\TimeFilteredDashboard;
use App\Filament\Admin\Pages\Dashboards\TransactionDashboard;
use App\Models\Payment;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
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

    public function test_requested_dashboards_render_a_full_width_period_section(): void
    {
        foreach ([
            AdminDashboard::class,
            SuperAdminDashboard::class,
            AccountantDashboard::class,
            ManagerDashboard::class,
            ReceptionDashboard::class,
            TransactionDashboard::class,
        ] as $dashboard) {
            $section = (new $dashboard)->filtersForm(Schema::make())->getComponents()[0];

            self::assertSame(['default' => 'full'], $section->getColumnSpan(), $dashboard);
        }
    }

    public function test_dashboard_period_form_defers_changes_until_apply_or_reset(): void
    {
        $section = (new AdminDashboard)->filtersForm(Schema::make())->getComponents()[0];
        $components = $section->getDefaultChildComponents();

        self::assertSame([], $components[0]->getStateBindingModifiers());
        self::assertSame([], $components[1]->getStateBindingModifiers());
        self::assertSame([], $components[2]->getStateBindingModifiers());

        $actions = collect($components)->first(
            fn ($component): bool => $component instanceof Actions,
        );

        self::assertInstanceOf(Actions::class, $actions);
        self::assertSame(['applyFilters', 'resetFilters'], array_map(
            fn (Action $action): string => $action->getName(),
            $actions->getDefaultChildComponents(),
        ));
    }

    public function test_kitchen_report_uses_deferred_inputs_and_has_a_reset_action(): void
    {
        $page = file_get_contents(resource_path('views/filament/admin/pages/kitchen-production-report.blade.php'));
        $view = file_get_contents(resource_path('views/components/filament/report-period-controls.blade.php'));

        self::assertStringContainsString('<x-filament.report-period-controls', $page);
        self::assertStringContainsString('wire:model="draftStartDate"', $view);
        self::assertStringContainsString('wire:model="draftEndDate"', $view);
        self::assertStringNotContainsString('wire:model.live="draftStartDate"', $view);
        self::assertStringNotContainsString('wire:model.live="draftEndDate"', $view);
        self::assertStringContainsString('wire:click="resetReportPeriod"', $view);
    }

    public function test_long_dashboards_put_priority_widgets_before_secondary_sections(): void
    {
        foreach ([
            SuperAdminDashboard::class,
            AdminDashboard::class,
            AccountantDashboard::class,
            ManagerDashboard::class,
        ] as $dashboardClass) {
            $components = (new $dashboardClass)->content(Schema::make())->getComponents();

            self::assertInstanceOf(Grid::class, $components[1], $dashboardClass);
            self::assertInstanceOf(Tabs::class, $components[2], $dashboardClass);
        }
    }

    public function test_super_admin_dashboard_sections_are_named_for_their_operational_domains(): void
    {
        $components = (new SuperAdminDashboard)->content(Schema::make())->getComponents();
        $tabs = $components[2];

        self::assertInstanceOf(Tabs::class, $tabs);
        self::assertSame([
            'Operations',
            'Finance',
            'Restaurant',
            'Kitchen',
        ], array_map(
            fn ($tab): string => $tab->getLabel(),
            $tabs->getDefaultChildComponents(),
        ));
    }

    public function test_dashboard_tabs_use_responsive_overflow_dropdowns(): void
    {
        foreach ([
            SuperAdminDashboard::class,
            AdminDashboard::class,
            AccountantDashboard::class,
            ManagerDashboard::class,
        ] as $dashboardClass) {
            $components = (new $dashboardClass)->content(Schema::make())->getComponents();
            $tabs = $components[2];

            self::assertInstanceOf(Tabs::class, $tabs, $dashboardClass);
            self::assertFalse($tabs->isScrollable(), $dashboardClass);
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
