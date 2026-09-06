<?php

namespace Tests\Feature;

use App\Enums\StaffAccountStatus;
use App\Filament\Admin\Resources\KitchenStockMovements\Pages\ListKitchenStockMovements;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class KitchenStockMovementDateFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_end_date_before_start_date_is_rejected_with_a_clear_message(): void
    {
        Livewire::actingAs($this->kitchenStaff())
            ->test(ListKitchenStockMovements::class)
            ->set('tableDeferredFilters.occurred_at.from', '2026-09-04')
            ->set('tableDeferredFilters.occurred_at.until', '2026-09-01')
            ->call('applyTableFilters')
            ->assertHasErrors([
                'tableDeferredFilters.occurred_at.until' => 'after_or_equal',
            ])
            ->assertSee('The end date must be on or after the start date.');
    }

    public function test_valid_date_range_is_applied_without_validation_errors(): void
    {
        Livewire::actingAs($this->kitchenStaff())
            ->test(ListKitchenStockMovements::class)
            ->set('tableDeferredFilters.occurred_at.from', '2026-09-01')
            ->set('tableDeferredFilters.occurred_at.until', '2026-09-04')
            ->call('applyTableFilters')
            ->assertHasNoErrors();
    }

    private function kitchenStaff(): User
    {
        $role = Role::findOrCreate('kitchen_staff', 'web');
        $role->givePermissionTo(Permission::findOrCreate('view kitchen stock movements', 'web'));
        $staff = User::factory()->create([
            'department' => 'kitchen_staff',
            'status' => StaffAccountStatus::Active,
        ]);
        $staff->syncRoles([$role]);

        return $staff;
    }
}
