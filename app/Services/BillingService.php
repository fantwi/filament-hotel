<?php

namespace App\Services;

class BillingService
{
    public function calculate(float $subtotal, ?string $promotionType = null, float $promotionValue = 0): array
    {
        $discount = $promotionType === 'percentage'
            ? min($subtotal, round($subtotal * max(0, $promotionValue) / 100, 2))
            : min($subtotal, max(0, $promotionValue));
        $net = round($subtotal - $discount, 2);
        $vat = round($net * config('billing.vat_rate') / 100, 2);
        $nhil = round($net * config('billing.nhil_rate') / 100, 2);
        $serviceCharge = round($net * config('billing.service_charge_rate') / 100, 2);

        return compact('subtotal', 'discount', 'net', 'vat', 'nhil', 'serviceCharge') + [
            'total' => round($net + $vat + $nhil + $serviceCharge, 2),
        ];
    }
}
