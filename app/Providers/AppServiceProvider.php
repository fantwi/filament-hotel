<?php

namespace App\Providers;

use App\Http\Responses\LogoutResponse as CustomLogoutResponse;
use Filament\Http\Responses\Auth\Contracts\LogoutResponse;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Models\Activity;

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
        //
        Activity::saving(function ($activity) {
            $activity->causer_id = auth()->id();
            $activity->properties = $activity->properties->merge([
                'ip_address' => request()->ip(),
            ]);
        });
        // Booking::observe(BookingObserver::class);
    }
}
