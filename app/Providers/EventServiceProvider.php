<?php

namespace App\Providers;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
        Event::listen(

            Login::class,

            function (Login $event) {

                activity()

                    ->causedBy(
                        $event->user
                    )

                    ->performedOn(
                        $event->user
                    )

                    ->log(
                        'User logged in'
                    );
            }
        );

        Event::listen(

            Logout::class,

            function (Logout $event) {

                if (! $event->user) {
                    return;
                }

                activity()

                    ->causedBy(
                        $event->user
                    )

                    ->performedOn(
                        $event->user
                    )

                    ->log(
                        'User logged out'
                    );
            }
        );
    }
}
