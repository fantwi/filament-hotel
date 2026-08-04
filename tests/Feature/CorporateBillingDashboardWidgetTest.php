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
}
