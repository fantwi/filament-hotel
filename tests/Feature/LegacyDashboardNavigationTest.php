<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\Dashboard;
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
}
