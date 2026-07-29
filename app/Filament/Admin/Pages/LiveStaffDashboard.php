<?php

namespace App\Filament\Admin\Pages;

use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;

class LiveStaffDashboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Live Staff Dashboard';

    protected static ?string $title = 'Live Staff Dashboard';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 30;

    protected string $view = 'filament.admin.pages.live-staff-dashboard';

    public $staff;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function mount()
    {
        $this->staff = User::with(['roles', 'activities'])
            ->whereHas('roles') // only staff
            ->get();
    }
}
