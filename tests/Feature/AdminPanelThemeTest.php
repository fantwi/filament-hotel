<?php

namespace Tests\Feature;

use App\Providers\Filament\AdminPanelProvider;
use Filament\Panel;
use Tests\TestCase;

class AdminPanelThemeTest extends TestCase
{
    public function test_admin_panel_uses_the_dedicated_filament_theme(): void
    {
        $panel = (new AdminPanelProvider($this->app))->panel(Panel::make());

        self::assertSame('resources/css/filament/admin/theme.css', $panel->getViteTheme());
        self::assertFileExists(resource_path('css/filament/admin/theme.css'));
    }

    public function test_vite_build_includes_every_application_entry_point(): void
    {
        $viteConfig = file_get_contents(base_path('vite.config.js'));

        self::assertStringContainsString("'resources/css/app.css'", $viteConfig);
        self::assertStringContainsString("'resources/css/filament/admin/theme.css'", $viteConfig);
        self::assertStringContainsString("'resources/js/app.js'", $viteConfig);
        self::assertStringContainsString("'resources/js/calendar.js'", $viteConfig);
    }
}
