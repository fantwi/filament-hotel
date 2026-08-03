<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Bookings\BookingResource;
use App\Filament\Admin\Resources\Payments\PaymentResource;
use App\Filament\Admin\Resources\Restaurants\RestaurantResource;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentResourceAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_accountants_cannot_create_or_edit_bookings_or_write_payments(): void
    {
        $accountant = User::factory()->create(['department' => 'accounting']);

        $this->actingAs($accountant);

        $this->assertFalse(BookingResource::canCreate());
        $this->assertFalse(BookingResource::canEdit(new Booking(['status' => 'pending'])));
        $this->assertFalse(PaymentResource::canCreate());
        $this->assertFalse(PaymentResource::canEdit(new Payment()));
        $this->assertFalse(PaymentResource::canDelete(new Payment()));
    }

    public function test_managers_have_explicit_content_access_but_not_implicit_payment_access(): void
    {
        $manager = User::factory()->create(['department' => 'management']);

        $this->actingAs($manager);

        $this->assertTrue(RestaurantResource::canViewAny());
        $this->assertTrue(RestaurantResource::canCreate());
        $this->assertFalse(PaymentResource::canViewAny());
        $this->assertFalse(PaymentResource::canCreate());
    }
}
