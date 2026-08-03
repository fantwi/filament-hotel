<?php

namespace Tests\Feature;

use Illuminate\Routing\Router;
use Tests\TestCase;

class RestaurantReservationAbuseProtectionTest extends TestCase
{
    public function test_public_reservation_submission_uses_the_named_rate_limiter(): void
    {
        $route = app(Router::class)
            ->getRoutes()
            ->getByName('restaurant.reserve.store');

        $this->assertNotNull($route);
        $this->assertContains(
            'throttle:restaurant-reservations',
            $route->gatherMiddleware()
        );
    }
}
