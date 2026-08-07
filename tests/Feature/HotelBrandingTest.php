<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\HotelSettings\HotelSettingResource;
use App\Models\HotelSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HotelBrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_hotel_branding_is_rendered_in_the_public_navigation_and_footer(): void
    {
        HotelSetting::create([
            'hotel_name' => 'Seaside Grand Hotel',
            'logo' => 'hotel-branding/seaside-logo.png',
            'primary_color' => '#C2410C',
            'secondary_color' => '#F59E0B',
            'footer_color' => '#172554',
        ]);

        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('Seaside Grand Hotel')
            ->assertSee('storage/hotel-branding/seaside-logo.png')
            ->assertSee('--hotel-primary: #C2410C', false)
            ->assertSee('--hotel-secondary: #F59E0B', false)
            ->assertSee('--hotel-footer: #172554', false);
    }

    public function test_only_admins_and_super_admins_can_manage_hotel_branding(): void
    {
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('manager', 'web');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $this->actingAs($admin);

        self::assertTrue(HotelSettingResource::canViewAny());
        self::assertTrue(HotelSettingResource::canCreate());

        HotelSetting::create(['hotel_name' => 'Seaside Grand Hotel']);

        self::assertFalse(HotelSettingResource::canCreate());

        $this->actingAs($manager);

        self::assertFalse(HotelSettingResource::canViewAny());
        self::assertFalse(HotelSettingResource::canCreate());
    }
}
