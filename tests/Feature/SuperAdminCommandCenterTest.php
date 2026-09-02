<?php

namespace Tests\Feature;

use App\Filament\Admin\Widgets\RoleDashboardOverview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use ReflectionMethod;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuperAdminCommandCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_command_center_uses_the_compact_layout(): void
    {
        $this->actingAsSuperAdmin();

        $widget = new RoleDashboardOverview;
        $method = new ReflectionMethod($widget, 'getViewData');
        $method->setAccessible(true);
        $viewData = $method->invoke($widget);

        self::assertArrayHasKey('compact', $viewData);
        self::assertTrue($viewData['compact']);
    }

    public function test_compact_command_center_does_not_repeat_the_reporting_period(): void
    {
        $this->actingAsSuperAdmin();

        Livewire::test(RoleDashboardOverview::class)
            ->assertSee('Super admin command center')
            ->assertSee('Business health')
            ->assertDontSee('Reporting period');
    }

    private function actingAsSuperAdmin(): void
    {
        $role = Role::findOrCreate('super_admin', 'web');
        $user = User::factory()->create(['department' => 'super_admin']);
        $user->assignRole($role);

        $this->actingAs($user);
    }
}
