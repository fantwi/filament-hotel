<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserResourceAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_edit_guests_for_corporate_linking_but_cannot_create_users(): void
    {
        $manager = User::factory()->create(['department' => 'management']);
        $guest = User::factory()->create(['department' => 'guest']);

        $this->actingAs($manager);

        $this->assertTrue(UserResource::canEdit($guest));
        $this->assertFalse(UserResource::canCreate());
    }

    public function test_admin_cannot_edit_privileged_users_but_can_edit_standard_users(): void
    {
        $admin = User::factory()->create(['department' => 'admin']);
        $guest = User::factory()->create(['department' => 'guest']);
        $superAdmin = User::factory()->create(['department' => 'super_admin']);

        $this->actingAs($admin);

        $this->assertTrue(UserResource::canEdit($guest));
        $this->assertFalse(UserResource::canEdit($superAdmin));
    }

    public function test_super_admin_can_edit_privileged_users(): void
    {
        $superAdmin = User::factory()->create(['department' => 'super_admin']);
        $admin = User::factory()->create(['department' => 'admin']);

        $this->actingAs($superAdmin);

        $this->assertTrue(UserResource::canEdit($admin));
    }
}
