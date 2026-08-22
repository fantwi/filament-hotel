<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\CorporateOrganization;
use App\Models\RestaurantOrder;
use App\Models\RestaurantReservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Encapsulates business rules for corporate credit service.
 */
class CorporateCreditService
{
    /**
     * Finds the enabled corporate organisation associated with the supplied user.
     */
    public function organizationFor(?User $user): ?CorporateOrganization
    {
        $organizationId = $user?->corporate_organization_id;

        if (! $organizationId) {
            return null;
        }

        $organization = CorporateOrganization::find($organizationId);

        return $organization?->is_credit_enabled ? $organization : null;
    }

    /**
     * Checks whether a charge remains within an organisation credit limit.
     */
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

    /**
     * Aggregates corporate billing, receivables, and credit exposure for dashboard presentation.
     */
    public function dashboardOverview(?Carbon $from = null, ?Carbon $until = null): array
    {
        $accounts = $this->accountExposure($from, $until);
        $totalCreditLimit = $accounts
            ->filter(fn (array $account): bool => $account['credit_limit'] !== null)
            ->sum('credit_limit');

        return [
            'active_accounts' => CorporateOrganization::query()
                ->where('is_credit_enabled', true)
                ->count(),
            'linked_guests' => User::query()
                ->where('department', 'guest')
                ->whereHas('corporateOrganization', fn ($query) => $query->where('is_credit_enabled', true))
                ->count(),
            'billed_this_month' => $from && $until
                ? $this->billedBetween($from, $until)
                : $this->billedSince(now()->startOfMonth()),
            'outstanding' => $accounts->sum('outstanding'),
            'credit_limit' => $totalCreditLimit,
            'available_credit' => $accounts
                ->whereNotNull('available_credit')
                ->sum('available_credit'),
            'accounts' => $accounts,
        ];
    }

    /**
     * Builds per-organisation outstanding balances and remaining credit amounts.
     */
    public function accountExposure(?Carbon $from = null, ?Carbon $until = null): Collection
    {
        $outstanding = $this->outstandingByOrganization($from, $until);

        return CorporateOrganization::query()
            ->where('is_credit_enabled', true)
            ->withCount([
                'users as linked_guests_count' => fn ($query) => $query->where('department', 'guest'),
            ])
            ->orderBy('name')
            ->get()
            ->map(function (CorporateOrganization $organization) use ($outstanding): array {
                $balance = (float) ($outstanding[$organization->id] ?? 0);
                $limit = $organization->credit_limit === null
                    ? null
                    : (float) $organization->credit_limit;

                return [
                    'id' => $organization->id,
                    'name' => $organization->name,
                    'linked_guests' => $organization->linked_guests_count,
                    'credit_limit' => $limit,
                    'outstanding' => $balance,
                    'available_credit' => $limit === null ? null : max(0, $limit - $balance),
                ];
            })
            ->values();
    }

    /**
     * Groups unpaid corporate transactions by organisation within optional date limits.
     */
    private function outstandingByOrganization(?Carbon $from = null, ?Carbon $until = null): array
    {
        $inRange = function (Builder $query) use ($from, $until): Builder {
            return $from && $until ? $query->whereBetween('created_at', [$from, $until]) : $query;
        };

        return $this->combineBalances([
            $inRange(Booking::query()->selectRaw('corporate_organization_id, SUM(total_price) as total')->whereNotNull('corporate_organization_id')->where('payment_status', 'pending')->whereNotIn('status', ['cancelled', 'expired', 'no_show']))->groupBy('corporate_organization_id')->pluck('total', 'corporate_organization_id')->all(),
            $inRange(ConferenceBooking::query()->selectRaw('corporate_organization_id, SUM(total_price) as total')->whereNotNull('corporate_organization_id')->where('payment_status', 'pending')->where('status', '!=', 'cancelled'))->groupBy('corporate_organization_id')->pluck('total', 'corporate_organization_id')->all(),
            $inRange(RestaurantReservation::query()->selectRaw('corporate_organization_id, SUM(reservation_fee) as total')->whereNotNull('corporate_organization_id')->where('payment_status', 'pending')->whereNotIn('status', ['cancelled', 'no_show']))->groupBy('corporate_organization_id')->pluck('total', 'corporate_organization_id')->all(),
            $inRange(RestaurantOrder::query()->selectRaw('corporate_organization_id, SUM(total) as total')->whereNotNull('corporate_organization_id')->where('payment_method', 'corporate_account')->where('payment_status', 'pending')->where('status', '!=', 'cancelled'))->groupBy('corporate_organization_id')->pluck('total', 'corporate_organization_id')->all(),
        ]);
    }

    /**
     * Totals valid corporate transactions billed from the supplied date to now.
     */
    private function billedSince(Carbon $from): float
    {
        return $this->billedBetween($from, now());
    }

    /**
     * Totals valid corporate transactions billed within the supplied date range.
     */
    private function billedBetween(Carbon $from, Carbon $until): float
    {
        return (float) Booking::query()->whereNotNull('corporate_organization_id')->whereNotIn('status', ['cancelled', 'expired', 'no_show'])->whereBetween('created_at', [$from, $until])->sum('total_price')
            + (float) ConferenceBooking::query()->whereNotNull('corporate_organization_id')->where('status', '!=', 'cancelled')->whereBetween('created_at', [$from, $until])->sum('total_price')
            + (float) RestaurantReservation::query()->whereNotNull('corporate_organization_id')->whereNotIn('status', ['cancelled', 'no_show'])->whereBetween('created_at', [$from, $until])->sum('reservation_fee')
            + (float) RestaurantOrder::query()->whereNotNull('corporate_organization_id')->where('payment_method', 'corporate_account')->where('status', '!=', 'cancelled')->whereBetween('created_at', [$from, $until])->sum('total');
    }

    /**
     * Merges balance totals collected from each corporate transaction type.
     */
    private function combineBalances(array $sources): array
    {
        $balances = [];

        foreach ($sources as $source) {
            foreach ($source as $organizationId => $total) {
                $balances[(int) $organizationId] = ($balances[(int) $organizationId] ?? 0) + (float) $total;
            }
        }

        return $balances;
    }

    /**
     * Calculates the organisation-wide unpaid corporate balance across every transaction type.
     */
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
