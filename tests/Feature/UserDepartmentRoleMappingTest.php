<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserDepartmentRoleMappingTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_configured_department_is_available_to_the_user_form(): void
    {
        self::assertSame(User::DEPARTMENTS, User::getDepartments());
        self::assertArrayHasKey('kitchen_manager', User::getDepartments());
        self::assertArrayHasKey('kitchen_staff', User::getDepartments());
    }

    #[DataProvider('departmentRoles')]
    public function test_department_assigns_the_expected_role(string $department, string $role): void
    {
        $user = User::factory()->create(['department' => $department]);

        self::assertTrue($user->fresh()->hasRole($role));
    }

    public static function departmentRoles(): array
    {
        return [
            'housekeeping' => ['housekeeping', 'housekeeping'],
            'kitchen' => ['kitchen', 'kitchen_staff'],
            'kitchen_manager' => ['kitchen_manager', 'kitchen_manager'],
            'kitchen_staff' => ['kitchen_staff', 'kitchen_staff'],
        ];
    }

    public function test_department_change_replaces_the_assigned_role(): void
    {
        $user = User::factory()->create(['department' => 'housekeeping']);

        $user->update(['department' => 'kitchen_manager']);

        $user = $user->fresh();

        self::assertFalse($user->hasRole('housekeeping'));
        self::assertTrue($user->hasRole('kitchen_manager'));
    }
}
