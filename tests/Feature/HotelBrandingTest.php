<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\HotelSettings\HotelSettingResource;
use App\Models\HotelSetting;
use App\Models\User;
use App\Providers\Filament\AdminPanelProvider;
use Filament\Panel;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
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

    public function test_admin_panel_uses_the_central_hotel_branding_settings(): void
    {
        Storage::fake('public');

        $branding = HotelSetting::create([
            'hotel_name' => 'Seaside Grand Hotel',
            'logo' => 'hotel-branding/seaside-logo.png',
            'primary_color' => '#C2410C',
            'secondary_color' => '#F59E0B',
            'footer_color' => '#172554',
        ]);

        $panel = (new AdminPanelProvider($this->app))->panel(Panel::make());

        self::assertSame($branding->hotel_name, $panel->getBrandName());
        self::assertInstanceOf(Htmlable::class, $panel->getBrandLogo());
        self::assertStringContainsString('Seaside Grand Hotel', $panel->getBrandLogo()->toHtml());
        self::assertStringContainsString(
            'http://localhost/storage/hotel-branding/seaside-logo.png',
            $panel->getBrandLogo()->toHtml(),
        );
        self::assertSame('#C2410C', $panel->getColors()['primary']);
        self::assertSame('#F59E0B', $panel->getColors()['info']);
    }

    public function test_admin_panel_logo_uses_the_current_request_host_instead_of_the_storage_disk_url(): void
    {
        config(['filesystems.disks.public.url' => 'https://wrong-storage-host.test/storage']);

        HotelSetting::create([
            'hotel_name' => 'Seaside Grand Hotel',
            'logo' => 'hotel-branding/seaside-logo.png',
        ]);

        Route::get('/_branding-logo-probe', function (): array {
            $panel = (new AdminPanelProvider($this->app))->panel(Panel::make());
            $brandLogo = $panel->getBrandLogo();

            return ['logo' => $brandLogo instanceof Htmlable ? $brandLogo->toHtml() : ''];
        });

        $response = $this->get('https://current-hotel.test/_branding-logo-probe');

        $response->assertOk();
        self::assertStringContainsString(
            'https://current-hotel.test/storage/hotel-branding/seaside-logo.png',
            $response->json('logo'),
        );
    }
}
