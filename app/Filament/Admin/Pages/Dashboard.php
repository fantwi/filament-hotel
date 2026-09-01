<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Pages\Dashboards\RoleDashboard;
use Filament\Pages\Page;

/**
 * Provides the dashboard Filament administration page.
 */
class Dashboard extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.admin.pages.redirecting';

    protected static ?string $title = 'Dashboard';

    protected static string|\UnitEnum|null $navigationGroup = 'Dashboards';

    protected static ?int $navigationSort = 0;

    /**
     * Redirects saved legacy URLs to the maintained role dashboard.
     */
    public function mount(): void
    {
        $this->redirect(RoleDashboard::getUrl());
    }

    /**
     * Controls whether this feature appears in the Filament navigation.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
