<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\CorporateOrganizations\CorporateOrganizationResource;
use App\Filament\Admin\Widgets\CorporateBillingOverview;
use App\Models\CorporateOrganization;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CorporateBillingDashboardWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_dashboard_renders_the_redesigned_corporate_billing_widget(): void
    {
        Permission::findOrCreate('view super admin dashboard', 'web');
        $role = Role::findOrCreate('super_admin', 'web');
        $user = User::factory()->create(['department' => 'super_admin']);
        $user->givePermissionTo('view super admin dashboard');
        $user->assignRole($role);

        $this->actingAs($user)
            ->get('/admin/super-admin-dashboard')
            ->assertOk()
            ->assertSee('Corporate billing and credit exposure')
            ->assertSee('Highest corporate exposures');
    }

    public function test_corporate_billing_widget_uses_responsive_credit_cards_and_utilisation_indicators(): void
    {
        $view = file_get_contents(resource_path('views/filament/admin/widgets/corporate-billing-overview.blade.php'));

        self::assertStringContainsString('Corporate billing and credit exposure', $view);
        self::assertStringContainsString('Credit utilisation', $view);
        self::assertStringContainsString('md:hidden', $view);
        self::assertStringContainsString('Billing guidance', $view);
    }

    public function test_corporate_billing_widget_clearly_separates_period_activity_from_all_time_exposure(): void
    {
        $role = Role::findOrCreate('accountant', 'web');
        $user = User::factory()->create(['department' => 'accountant']);
        $user->assignRole($role);

        Livewire::actingAs($user)
            ->test(CorporateBillingOverview::class, ['pageFilters' => [
                'period' => 'weekly',
                'start_date' => '2026-08-03',
                'end_date' => '2026-08-09',
            ]])
            ->assertSee('Applied reporting period')
            ->assertSee('Aug 3, 2026 - Aug 9, 2026')
            ->assertSeeInOrder([
                'Selected-period activity',
                'Corporate billing raised',
                'Current exposure — all periods',
                'Total outstanding',
                'Available credit',
                'Enabled accounts',
            ]);
    }

    public function test_corporate_billing_widget_links_its_bounded_exposure_summary_to_the_full_register(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $role = Role::findOrCreate('accountant', 'web');
        $user = User::factory()->create(['department' => 'accountant']);
        $user->assignRole($role);
        $url = CorporateOrganizationResource::getUrl('index');

        Livewire::actingAs($user)
            ->test(CorporateBillingOverview::class)
            ->assertSee('Highest corporate exposures')
            ->assertSee('Showing the five accounts with the highest outstanding balances.')
            ->assertSee('View all corporate accounts')
            ->assertSeeHtml('href="'.$url.'"');
    }

    public function test_accountant_has_read_only_access_to_the_complete_corporate_account_register(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $role = Role::findOrCreate('accountant', 'web');
        $user = User::factory()->create(['department' => 'accountant']);
        $user->assignRole($role);
        $organization = CorporateOrganization::query()->create([
            'name' => 'Read-only Corporate Account',
            'credit_limit' => 5000,
            'is_credit_enabled' => true,
        ]);

        $this->actingAs($user);

        self::assertTrue(CorporateOrganizationResource::canViewAny());
        self::assertFalse(CorporateOrganizationResource::canCreate());
        self::assertFalse(CorporateOrganizationResource::canEdit($organization));
        self::assertFalse(CorporateOrganizationResource::canDelete($organization));

        $this->get(CorporateOrganizationResource::getUrl('index'))
            ->assertOk()
            ->assertSee('Read-only Corporate Account');
    }
}
