<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Models\User;
use BackedEnum;

class LiveStaffDashboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';
    protected static string|null $navigationLabel = 'Live Staff Dashboard';
    protected static string|null $title = 'Live Staff Dashboard';
    protected static string|\UnitEnum|null $navigationGroup = 'System';
    protected static ?int $navigationSort = 30;
    protected string $view = 'filament.admin.pages.live-staff-dashboard';

    public $staff;

    public function mount()
    {
        $this->staff = User::with(['roles', 'activities'])
            ->whereHas('roles') // only staff
            ->get();
    }
}
