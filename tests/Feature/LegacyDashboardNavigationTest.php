<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\Dashboard;
use App\Filament\Admin\Pages\LiveStaffDashboard;
use ReflectionClass;
use Tests\TestCase;

class LegacyDashboardNavigationTest extends TestCase
{
    public function test_legacy_dashboard_is_not_registered_in_the_sidebar_navigation(): void
    {
        $reflection = new ReflectionClass(Dashboard::class);
        $property = $reflection->getProperty('shouldRegisterNavigation');
        $property->setAccessible(true);

        self::assertFalse($property->getValue());
    }

    public function test_legacy_live_staff_dashboard_is_not_registered_in_the_sidebar_navigation(): void
    {
        self::assertFalse(LiveStaffDashboard::shouldRegisterNavigation());
    }
}
