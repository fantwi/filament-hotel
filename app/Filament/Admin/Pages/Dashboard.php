<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;

class Dashboard extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';
    protected string $view = 'filament.admin.pages.dashboard';
    protected static ?string $title = 'Dashboard';
    protected static string|\UnitEnum|null $navigationGroup = 'Dashboards';
    protected static ?int $navigationSort = 0;

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Admin\Widgets\StatsOverview::class,
        ];
    }
}
