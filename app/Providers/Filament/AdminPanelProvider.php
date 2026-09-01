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
use App\Models\HotelSetting;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Storage;
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
            // ->viteTheme('resources/css/app.css') // FAA added this
            ->brandName(fn (): string => $this->hotelBrandName())
            ->brandLogo(fn (): ?string => $this->hotelBrandLogo())
            ->brandLogoHeight('2.25rem')
            ->colors(fn (): array => [
                'primary' => $this->hotelBrandColor('primary_color', '#F59E0B'),
                'info' => $this->hotelBrandColor('secondary_color', '#0EA5E9'),
            ])
            ->navigationGroups([
                'Dashboards',
                'Accommodation',
                'Conferences',
                'Restaurant Sales',
                'Kitchen & Inventory',
                'Guests & Communications',
                'Finance',
                'Reports',
                'Access & Administration',
                'Hotel Configuration',
            ])
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

        return filled($logo) ? Storage::disk('public')->url((string) $logo) : null;
    }

    /**
     * Resolves a validated centrally managed color for the Filament palette.
     */
    private function hotelBrandColor(string $attribute, string $fallback): string
    {
        return HotelSetting::current()->color($attribute, $fallback);
    }
}
