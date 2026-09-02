<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\Dashboards\AccountantDashboard;
use App\Filament\Admin\Widgets\AccountantPaymentActivityChart;
use App\Filament\Admin\Widgets\AccountantReceivablesStats;
use App\Filament\Admin\Widgets\AccountantStats;
use App\Filament\Admin\Widgets\CorporateBillingOverview;
use App\Filament\Admin\Widgets\RecentPayments;
use App\Filament\Admin\Widgets\RoleDashboardOverview;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccountantDashboardLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_finance_center_and_summary_metrics_are_prioritized_above_finance_details(): void
    {
        $dashboard = new AccountantDashboard;
        $method = new ReflectionMethod($dashboard, 'dashboardWidgetLayout');
        $method->setAccessible(true);
        [$priorityWidgets, $sections] = $method->invoke($dashboard);

        self::assertSame([
            RoleDashboardOverview::class,
            AccountantStats::class,
            AccountantReceivablesStats::class,
        ], $priorityWidgets);
        self::assertSame([
            'Finance' => [
                CorporateBillingOverview::class,
                AccountantPaymentActivityChart::class,
                RecentPayments::class,
            ],
        ], $sections);
    }

    public function test_authenticated_accountant_sees_finance_center_before_finance_details_without_guidance_tab(): void
    {
        $this->actingAsAccountant();

        $this->get('/admin/accountant-dashboard')
            ->assertOk()
            ->assertSeeInOrder([
                'Dashboard period',
                RoleDashboardOverview::class,
                AccountantStats::class,
                AccountantReceivablesStats::class,
                'Finance',
                CorporateBillingOverview::class,
                AccountantPaymentActivityChart::class,
                RecentPayments::class,
            ], escape: false)
            ->assertDontSee('Guidance');
    }

    private function actingAsAccountant(): User
    {
        $permission = Permission::findOrCreate('view accountant dashboard', 'web');
        $role = Role::findOrCreate('accountant', 'web');
        $user = User::factory()->create(['department' => 'accountant']);
        $user->assignRole($role);
        $user->givePermissionTo($permission);

        $this->actingAs($user);

        return $user;
    }
}
