<?php

namespace Tests\Feature;

use App\Models\BillingSetting;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uses_the_rates_configured_in_filament_billing_settings(): void
    {
        BillingSetting::create([
            'vat_rate' => 10,
            'nhil_rate' => 2.5,
            'service_charge_rate' => 5,
        ]);

        $totals = app(BillingService::class)->calculate(100, 'percentage', 10);

        self::assertSame(100.0, $totals['subtotal']);
        self::assertSame(10.0, $totals['discount']);
        self::assertSame(90.0, $totals['net']);
        self::assertSame(9.0, $totals['vat']);
        self::assertSame(2.25, $totals['nhil']);
        self::assertSame(4.5, $totals['serviceCharge']);
        self::assertSame(105.75, $totals['total']);
    }

    public function test_it_caps_fixed_discounts_at_the_subtotal_before_charges_are_added(): void
    {
        $totals = app(BillingService::class)->calculate(50, 'fixed', 75);

        self::assertSame(50.0, $totals['discount']);
        self::assertSame(0.0, $totals['net']);
        self::assertSame(0.0, $totals['vat']);
        self::assertSame(0.0, $totals['nhil']);
        self::assertSame(0.0, $totals['serviceCharge']);
        self::assertSame(0.0, $totals['total']);
    }
}
