<?php

namespace App\Services;

use App\Models\BillingSetting;

/**
 * Encapsulates business rules for billing service.
 */
class BillingService
{
    /**
     * Calculates  using the current business rules.
     */
    public function calculate(float $subtotal, ?string $promotionType = null, float $promotionValue = 0): array
    {
        $subtotal = round(max(0, $subtotal), 2);
        $discount = $promotionType === 'percentage'
            ? min($subtotal, round($subtotal * max(0, $promotionValue) / 100, 2))
            : min($subtotal, round(max(0, $promotionValue), 2));
        $net = round($subtotal - $discount, 2);
        $rates = $this->rates();
        $vat = round($net * $rates['vat'] / 100, 2);
        $nhil = round($net * $rates['nhil'] / 100, 2);
        $serviceCharge = round($net * $rates['service_charge'] / 100, 2);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'net' => $net,
            'vat' => $vat,
            'nhil' => $nhil,
            'serviceCharge' => $serviceCharge,
            'vatRate' => $rates['vat'],
            'nhilRate' => $rates['nhil'],
            'serviceChargeRate' => $rates['service_charge'],
            'total' => round($net + $vat + $nhil + $serviceCharge, 2),
        ];
    }

    /**
     * Returns the currently configured tax and service-charge rates.
     */
    public function rates(): array
    {
        $settings = BillingSetting::query()->latest('id')->first();

        return [
            'vat' => (float) ($settings?->vat_rate ?? config('billing.vat_rate')),
            'nhil' => (float) ($settings?->nhil_rate ?? config('billing.nhil_rate')),
            'service_charge' => (float) ($settings?->service_charge_rate ?? config('billing.service_charge_rate')),
        ];
    }
}
