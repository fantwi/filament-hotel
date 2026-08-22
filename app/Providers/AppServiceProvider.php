<?php

namespace App\Providers;

use App\Http\Responses\LogoutResponse as CustomLogoutResponse;
use App\Models\HotelSetting;
use Filament\Http\Responses\Auth\Contracts\LogoutResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

/**
 * Registers application services for app service provider.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        $this->app->bind(LogoutResponse::class, CustomLogoutResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()/* : void */
    {
        View::composer(['layouts.guest', 'layouts.auth', 'layouts.app'], function ($view): void {
            $view->with('hotelBranding', HotelSetting::current());
        });

        RateLimiter::for('restaurant-reservations', function (Request $request): array {
            $emailKey = hash(
                'sha256',
                Str::lower(trim((string) $request->input('guest_email', 'unknown')))
            );

            return [
                Limit::perMinute(3)->by('restaurant-reservations:ip:'.$request->ip()),
                Limit::perHour(10)->by('restaurant-reservations:email:'.$emailKey),
            ];
        });

        Activity::saving(function ($activity) {
            $activity->causer_id = auth()->id();
            $activity->properties = $activity->properties->merge([
                'ip_address' => request()->ip(),
            ]);
        });
        // Booking::observe(BookingObserver::class);
    }
}
