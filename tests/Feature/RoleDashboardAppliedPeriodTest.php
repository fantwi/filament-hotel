<?php

namespace Tests\Feature;

use App\Filament\Admin\Widgets\RoleDashboardOverview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleDashboardAppliedPeriodTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('nonCompactDashboardRoles')]
    public function test_role_overview_identifies_the_committed_widget_range_as_applied(string $roleName): void
    {
        $role = Role::findOrCreate($roleName, 'web');
        $user = User::factory()->create(['department' => $roleName]);
        $user->assignRole($role);

        Livewire::actingAs($user)
            ->test(RoleDashboardOverview::class, ['pageFilters' => [
                'period' => 'weekly',
                'start_date' => '2026-08-02',
                'end_date' => '2026-08-09',
            ]])
            ->assertSee('Applied reporting period')
            ->assertSee('Aug 2, 2026 - Aug 9, 2026');
    }

    public static function nonCompactDashboardRoles(): array
    {
        return [
            'admin' => ['admin'],
            'accountant' => ['accountant'],
            'manager' => ['manager'],
            'receptionist' => ['receptionist'],
        ];
    }
}
