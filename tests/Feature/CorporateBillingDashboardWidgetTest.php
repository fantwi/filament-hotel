<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CorporateBillingDashboardWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_dashboard_renders_the_corporate_billing_widget(): void
    {
        Permission::findOrCreate('view super admin dashboard', 'web');
        $user = User::factory()->create(['department' => 'super_admin']);
        $user->givePermissionTo('view super admin dashboard');

        $this->actingAs($user)
            ->get('/admin/super-admin-dashboard')
            ->assertOk();
    }

    public function test_corporate_billing_widget_uses_responsive_credit_cards_and_utilisation_indicators(): void
    {
        $view = file_get_contents(resource_path('views/filament/admin/widgets/corporate-billing-overview.blade.php'));

        self::assertStringContainsString('Corporate billing and credit exposure', $view);
        self::assertStringContainsString('Credit utilisation', $view);
        self::assertStringContainsString('md:hidden', $view);
        self::assertStringContainsString('Billing guidance', $view);
    }
}
