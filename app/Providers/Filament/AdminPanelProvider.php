<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Pages\Auth\EditProfile;
use App\Filament\Admin\Pages\Dashboards\RoleDashboard;
use App\Filament\Admin\Widgets\BestSellingMenuItems;
use App\Filament\Admin\Widgets\BookingTrendChart;
use App\Filament\Admin\Widgets\HotelStatistics;
use App\Filament\Admin\Widgets\KitchenOrderQueue;
use App\Filament\Admin\Widgets\KitchenOrderStats;
use App\Filament\Admin\Widgets\MonthlyRevenueChart;
use App\Filament\Admin\Widgets\RestaurantOrderStats;
use App\Filament\Admin\Widgets\RestaurantOrderStatusChart;
use App\Filament\Admin\Widgets\RestaurantRevenueChart;
use App\Filament\Admin\Widgets\RevenueStats;
use App\Filament\Admin\Widgets\StaffStats;
use App\Http\Middleware\EnforceStaffAccountStatus;
use App\Http\Middleware\UpdateLastSeen;
use App\Models\HotelSetting;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Supports the admin panel provider Filament administration feature.
 */
class AdminPanelProvider extends PanelProvider
{
    /**
     * Configures panel for the Filament administration interface.
     */
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default() // newly added by FAA
            ->id('admin')
            ->path('admin')
            ->login() // newly added by FAA
            ->profile(EditProfile::class, isSimple: false)
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->brandName(fn (): string => $this->hotelBrandName())
            ->brandLogo(fn (): ?string => $this->hotelBrandLogo())
            ->brandLogoHeight('2.25rem')
            ->colors(fn (): array => [
                'primary' => $this->hotelBrandColor('primary_color', '#F59E0B'),
                'info' => $this->hotelBrandColor('secondary_color', '#0EA5E9'),
            ])
            ->navigationGroups([
                NavigationGroup::make('Dashboards')->collapsed(fn (NavigationGroup $group): bool => ! $group->isActive())->collapsible(),
                NavigationGroup::make('Accommodation')->collapsed(fn (NavigationGroup $group): bool => ! $group->isActive())->collapsible(),
                NavigationGroup::make('Conferences')->collapsed(fn (NavigationGroup $group): bool => ! $group->isActive())->collapsible(),
                NavigationGroup::make('Restaurant Sales')->collapsed(fn (NavigationGroup $group): bool => ! $group->isActive())->collapsible(),
                NavigationGroup::make('Kitchen & Inventory')->collapsed(fn (NavigationGroup $group): bool => ! $group->isActive())->collapsible(),
                NavigationGroup::make('Guests & Communications')->collapsed(fn (NavigationGroup $group): bool => ! $group->isActive())->collapsible(),
                NavigationGroup::make('Finance')->collapsed(fn (NavigationGroup $group): bool => ! $group->isActive())->collapsible(),
                NavigationGroup::make('Reports')->collapsed(fn (NavigationGroup $group): bool => ! $group->isActive())->collapsible(),
                NavigationGroup::make('Access & Administration')->collapsed(fn (NavigationGroup $group): bool => ! $group->isActive())->collapsible(),
                NavigationGroup::make('Hotel Configuration')->collapsed(fn (NavigationGroup $group): bool => ! $group->isActive())->collapsible(),
            ])
            ->navigation(function (): bool {
                $user = auth()->user();

                return ! $user?->isStaff() || $user->hasActiveStaffAccount();
            })
            ->renderHook(
                PanelsRenderHook::PAGE_START,
                fn () => view('filament.admin.staff-account-notice'),
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_NAV_START,
                fn () => view('filament.admin.navigation-state', [
                    'activeGroupLabels' => collect(filament()->getNavigation())
                        ->filter(fn (NavigationGroup $group): bool => $group->isActive())
                        ->map(fn (NavigationGroup $group): ?string => $group->getLabel())
                        ->filter()
                        ->values()
                        ->all(),
                ]),
            )
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\Filament\Admin\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\Filament\Admin\Pages')
            ->pages([
                RoleDashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\Filament\Admin\Widgets')
            ->widgets([
                AccountWidget::class,
                // FilamentInfoWidget::class,
                HotelStatistics::class,
                MonthlyRevenueChart::class,
                BookingTrendChart::class,
                KitchenOrderStats::class,
                KitchenOrderQueue::class,
                RestaurantOrderStats::class,
                RestaurantRevenueChart::class,
                RestaurantOrderStatusChart::class,
                BestSellingMenuItems::class,
                RevenueStats::class,
                StaffStats::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                UpdateLastSeen::class,
                EnforceStaffAccountStatus::class,
            ])
            ->persistentMiddleware([
                UpdateLastSeen::class,
                EnforceStaffAccountStatus::class,
            ]);
    }

    /**
     * Resolves the centrally managed hotel name for the Filament header.
     */
    private function hotelBrandName(): string
    {
        $name = HotelSetting::current()->hotel_name;

        return filled($name) ? (string) $name : (string) config('app.name', 'Laravel');
    }

    /**
     * Resolves the centrally managed hotel logo URL for the Filament header.
     */
    private function hotelBrandLogo(): ?string
    {
        $logo = HotelSetting::current()->logo;

        return filled($logo) ? asset('storage/'.$logo) : null;
    }

    /**
     * Resolves a validated centrally managed color for the Filament palette.
     */
    private function hotelBrandColor(string $attribute, string $fallback): string
    {
        return HotelSetting::current()->color($attribute, $fallback);
    }
}
