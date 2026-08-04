<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\CorporateOrganization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CorporateGuestLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authorized_manager_can_link_a_guest_to_an_enabled_corporate_account(): void
    {
        $manager = User::factory()->create(['department' => 'management']);
        $guest = User::factory()->create(['department' => 'guest']);
        $organization = CorporateOrganization::create([
            'name' => 'Corporate Guest Account',
            'is_credit_enabled' => true,
        ]);

        $this->actingAs($manager);

        self::assertTrue(UserResource::canEdit($guest));

        $guest->update([
            'corporate_organization_id' => $organization->id,
        ]);

        $guest->refresh();

        self::assertSame($organization->id, $guest->corporate_organization_id);
        self::assertSame($organization->id, $guest->corporateOrganization->id);
    }

    public function test_a_manager_cannot_edit_a_staff_account_through_the_guest_linking_screen(): void
    {
        $manager = User::factory()->create(['department' => 'management']);
        $staffMember = User::factory()->create(['department' => 'reception']);

        $this->actingAs($manager);

        self::assertFalse(UserResource::canEdit($staffMember));
    }
}
