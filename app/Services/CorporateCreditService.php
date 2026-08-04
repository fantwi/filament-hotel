<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\CorporateOrganization;
use App\Models\RestaurantOrder;
use App\Models\RestaurantReservation;
use App\Models\User;

class CorporateCreditService
{
    public function organizationFor(?User $user): ?CorporateOrganization
    {
        $organizationId = $user?->corporate_organization_id;

        if (! $organizationId) {
            return null;
        }

        $organization = CorporateOrganization::find($organizationId);

        return $organization?->is_credit_enabled ? $organization : null;
    }

    public function canCharge(CorporateOrganization $organization, float $amount): bool
    {
        if (! $organization->is_credit_enabled || $amount < 0) {
            return false;
        }

        if ($organization->credit_limit === null) {
            return true;
        }

        return $this->outstandingBalance($organization) + $amount
            <= (float) $organization->credit_limit + 0.004;
    }

    public function outstandingBalance(CorporateOrganization $organization): float
    {
        $organizationId = $organization->id;

        return (float) Booking::query()
            ->where('corporate_organization_id', $organizationId)
            ->where('payment_status', 'pending')
            ->whereNotIn('status', ['cancelled', 'expired', 'no_show'])
            ->sum('total_price')
            + (float) ConferenceBooking::query()
                ->where('corporate_organization_id', $organizationId)
                ->where('payment_status', 'pending')
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->sum('total_price')
            + (float) RestaurantReservation::query()
                ->where('corporate_organization_id', $organizationId)
                ->where('payment_status', 'pending')
                ->whereNotIn('status', ['cancelled', 'no_show'])
                ->sum('reservation_fee')
            + (float) RestaurantOrder::query()
                ->where('corporate_organization_id', $organizationId)
                ->where('payment_method', 'corporate_account')
                ->where('payment_status', 'pending')
                ->where('status', '!=', 'cancelled')
                ->sum('total');
    }
}
