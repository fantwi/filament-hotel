<?php

namespace App\Filament\Admin\Pages;

use App\Models\CorporateOrganization;
use Filament\Pages\Page;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;

/**
 * Provides the corporate receivables Filament administration page.
 */
class CorporateReceivables extends Page
{
    use WithPagination;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|\UnitEnum|null $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Corporate Receivables';

    protected static ?int $navigationSort = 11;

    protected string $view = 'filament.admin.pages.corporate-receivables';

    public string $draftTransactionType = 'all';

    public string $draftSearch = '';

    public string $draftOrganizationId = '';

    public string $draftFromDate = '';

    public string $draftUntilDate = '';

    public string $transactionType = 'all';

    public string $search = '';

    public string $organizationId = '';

    public string $fromDate = '';

    public string $untilDate = '';

    public int $perPage = 25;

    /**
     * Summarizes the purpose of the corporate settlement queue.
     */
    public function getSubheading(): ?string
    {
        return 'Track corporate credit transactions and record payments received outside the online checkout.';
    }

    /**
     * Determines whether the current user may access this feature.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'accountant']) ?? false;
    }

    /**
     * Applies a validated transaction-dashboard scope from the query string.
     */
    public function mount(): void
    {
        $transactionType = (string) request()->query('transaction_type', 'all');
        $fromDate = (string) request()->query('from_date', '');
        $untilDate = (string) request()->query('until_date', '');

        if (! in_array($transactionType, ['all', 'booking', 'conference', 'reservation', 'order'], true)) {
            $transactionType = 'all';
        }

        $fromDate = $this->isDate($fromDate) ? $fromDate : '';
        $untilDate = $this->isDate($untilDate) ? $untilDate : '';

        if ($fromDate !== '' && $untilDate !== '' && $fromDate > $untilDate) {
            $fromDate = '';
            $untilDate = '';
        }

        $this->draftTransactionType = $this->transactionType = $transactionType;
        $this->draftFromDate = $this->fromDate = $fromDate;
        $this->draftUntilDate = $this->untilDate = $untilDate;
    }

    /**
     * Returns the current page of receivables as a paginator.
     *
     * The query combines the four transaction sources in SQL, allowing the
     * database to filter, sort, count, and paginate without loading the full
     * corporate receivables queue into PHP memory.
     */
    public function paginatedReceivables(): LengthAwarePaginator
    {
        $perPage = max(10, min(100, $this->perPage));
        $paginator = $this->filteredReceivablesQuery()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'receivables_page');

        $paginator->setCollection(
            $paginator->getCollection()->map(fn (object $row): array => $this->normaliseRow($row)),
        );

        return $paginator;
    }

    /**
     * Keeps the previous collection-shaped API for widgets and integrations.
     * New page code should use paginatedReceivables() instead.
     */
    public function receivables(): Collection
    {
        return $this->paginatedReceivables()->getCollection();
    }

    /**
     * Summarizes either a supplied collection or the complete filtered queue.
     */
    public function summary(?Collection $receivables = null): array
    {
        if ($receivables !== null) {
            return $this->summaryFromCollection($receivables);
        }

        $totals = DB::query()
            ->fromSub($this->filteredReceivablesQuery(), 'corporate_receivables_summary')
            ->selectRaw('COUNT(*) as total_count, COALESCE(SUM(amount), 0) as total_amount')
            ->first();

        $byTypeRows = DB::query()
            ->fromSub($this->filteredReceivablesQuery(), 'corporate_receivables_by_type')
            ->select('type')
            ->selectRaw('COUNT(*) as type_count, COALESCE(SUM(amount), 0) as type_amount')
            ->groupBy('type')
            ->get();

        $byType = $byTypeRows->mapWithKeys(
            fn (object $row): array => [(string) $row->type => (int) $row->type_count],
        )->all();
        $byTypeAmount = $byTypeRows->mapWithKeys(
            fn (object $row): array => [(string) $row->type => (float) $row->type_amount],
        )->all();

        $organizations = DB::query()
            ->fromSub($this->filteredReceivablesQuery(), 'corporate_receivables_organizations')
            ->whereNotNull('organization_id')
            ->distinct()
            ->count('organization_id');

        return [
            'count' => (int) ($totals->total_count ?? 0),
            'total' => (float) ($totals->total_amount ?? 0),
            'organizations' => (int) $organizations,
            'by_type' => $byType,
            'by_type_amount' => $byTypeAmount,
        ];
    }

    /**
     * Returns organisation choices for the page filter.
     */
    public function organizationOptions(): array
    {
        return CorporateOrganization::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * Validates draft filters, applies them, and returns the first page.
     */
    public function applyFilters(): void
    {
        $this->validate([
            'draftFromDate' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:draftUntilDate'],
            'draftUntilDate' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:draftFromDate'],
            'perPage' => ['required', 'integer', 'min:10', 'max:100'],
        ]);

        $this->transactionType = $this->draftTransactionType;
        $this->search = $this->draftSearch;
        $this->organizationId = $this->draftOrganizationId;
        $this->fromDate = $this->draftFromDate;
        $this->untilDate = $this->draftUntilDate;

        $this->resetPage('receivables_page');
    }

    /**
     * Clears all queue filters.
     */
    public function clearFilters(): void
    {
        $this->draftTransactionType = 'all';
        $this->draftSearch = '';
        $this->draftOrganizationId = '';
        $this->draftFromDate = '';
        $this->draftUntilDate = '';
        $this->transactionType = 'all';
        $this->search = '';
        $this->organizationId = '';
        $this->fromDate = '';
        $this->untilDate = '';
        $this->perPage = 25;
        $this->resetPage('receivables_page');
        $this->resetValidation();
    }

    /**
     * Resets pagination when the live pagination preference changes.
     */
    public function updatedPerPage(): void
    {
        $this->resetPage('receivables_page');
    }

    /**
     * Builds the normalised SQL query used by the table and summary cards.
     */
    private function filteredReceivablesQuery(): Builder
    {
        $query = DB::query()->fromSub($this->receivablesUnion(), 'corporate_receivables');

        if ($this->transactionType !== 'all' && in_array($this->transactionType, ['booking', 'conference', 'reservation', 'order'], true)) {
            $query->where('type', $this->transactionType);
        }

        if ($this->organizationId !== '' && ctype_digit($this->organizationId)) {
            $query->where('organization_id', (int) $this->organizationId);
        }

        if ($this->isDate($this->fromDate)) {
            $query->whereDate('created_at', '>=', $this->fromDate);
        }

        if ($this->isDate($this->untilDate)) {
            $query->whereDate('created_at', '<=', $this->untilDate);
        }

        $search = trim($this->search);
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function (Builder $searchQuery) use ($like): void {
                $searchQuery
                    ->where('organization', 'like', $like)
                    ->orWhere('guest_first_name', 'like', $like)
                    ->orWhere('guest_last_name', 'like', $like)
                    ->orWhere('fallback_guest_name', 'like', $like)
                    ->orWhere('label', 'like', $like);
            });
        }

        return $query->where('amount', '>', 0);
    }

    /**
     * Combines outstanding room, conference, table, and food transactions.
     */
    private function receivablesUnion(): Builder
    {
        $booking = DB::table('bookings as booking')
            ->leftJoinSub($this->paidTotals('booking_id'), 'booking_paid', 'booking_paid.booking_id', '=', 'booking.id')
            ->leftJoin('guests as booking_guest', 'booking_guest.id', '=', 'booking.guest_id')
            ->leftJoin('corporate_organizations as booking_organization', 'booking_organization.id', '=', 'booking.corporate_organization_id')
            ->whereNotNull('booking.corporate_organization_id')
            ->whereNotIn('booking.payment_status', ['paid', 'completed', 'refunded'])
            ->whereNotIn('booking.status', ['cancelled', 'no_show', 'expired'])
            ->selectRaw("'booking' as type, 'Room booking' as label, booking.id, booking.corporate_organization_id as organization_id, COALESCE(booking_organization.name, 'Corporate account') as organization, booking_guest.first_name as guest_first_name, booking_guest.last_name as guest_last_name, NULL as fallback_guest_name, (booking.total_price - COALESCE(booking_paid.paid_amount, 0)) as amount, booking.payment_status, booking.status, booking.created_at");

        $conference = DB::table('conference_bookings as conference')
            ->leftJoinSub($this->paidTotals('conference_booking_id'), 'conference_paid', 'conference_paid.conference_booking_id', '=', 'conference.id')
            ->leftJoin('guests as conference_guest', 'conference_guest.id', '=', 'conference.guest_id')
            ->leftJoin('corporate_organizations as conference_organization', 'conference_organization.id', '=', 'conference.corporate_organization_id')
            ->whereNotNull('conference.corporate_organization_id')
            ->whereNotIn('conference.payment_status', ['paid', 'completed', 'refunded'])
            ->whereNotIn('conference.status', ['cancelled', 'no_show', 'expired'])
            ->selectRaw("'conference' as type, 'Conference booking' as label, conference.id, conference.corporate_organization_id as organization_id, COALESCE(conference_organization.name, 'Corporate account') as organization, conference_guest.first_name as guest_first_name, conference_guest.last_name as guest_last_name, NULL as fallback_guest_name, (conference.total_price - COALESCE(conference_paid.paid_amount, 0)) as amount, conference.payment_status, conference.status, conference.created_at");

        $reservation = DB::table('restaurant_reservations as reservation')
            ->leftJoinSub($this->paidTotals('restaurant_reservation_id'), 'reservation_paid', 'reservation_paid.restaurant_reservation_id', '=', 'reservation.id')
            ->leftJoin('guests as reservation_guest', 'reservation_guest.id', '=', 'reservation.guest_id')
            ->leftJoin('corporate_organizations as reservation_organization', 'reservation_organization.id', '=', 'reservation.corporate_organization_id')
            ->whereNotNull('reservation.corporate_organization_id')
            ->whereNotIn('reservation.payment_status', ['paid', 'completed', 'refunded'])
            ->whereNotIn('reservation.status', ['cancelled', 'no_show', 'expired'])
            ->selectRaw("'reservation' as type, 'Table reservation' as label, reservation.id, reservation.corporate_organization_id as organization_id, COALESCE(reservation_organization.name, 'Corporate account') as organization, reservation_guest.first_name as guest_first_name, reservation_guest.last_name as guest_last_name, reservation.guest_name as fallback_guest_name, (reservation.reservation_fee - COALESCE(reservation_paid.paid_amount, 0)) as amount, reservation.payment_status, reservation.status, reservation.created_at");

        $order = DB::table('restaurant_orders as food_order')
            ->leftJoinSub($this->paidTotals('restaurant_order_id'), 'order_paid', 'order_paid.restaurant_order_id', '=', 'food_order.id')
            ->leftJoin('guests as order_guest', 'order_guest.id', '=', 'food_order.guest_id')
            ->leftJoin('corporate_organizations as order_organization', 'order_organization.id', '=', 'food_order.corporate_organization_id')
            ->whereNotNull('food_order.corporate_organization_id')
            ->whereNotIn('food_order.payment_status', ['paid', 'completed', 'refunded'])
            ->whereNotIn('food_order.status', ['cancelled', 'expired'])
            ->selectRaw("'order' as type, 'Food order' as label, food_order.id, food_order.corporate_organization_id as organization_id, COALESCE(order_organization.name, 'Corporate account') as organization, order_guest.first_name as guest_first_name, order_guest.last_name as guest_last_name, food_order.customer_email as fallback_guest_name, (food_order.total - COALESCE(order_paid.paid_amount, 0)) as amount, food_order.payment_status, food_order.status, food_order.created_at");

        return $booking
            ->unionAll($conference)
            ->unionAll($reservation)
            ->unionAll($order);
    }

    /**
     * Aggregates completed payments for one transaction foreign key.
     */
    private function paidTotals(string $foreignKey): Builder
    {
        return DB::table('payments')
            ->select($foreignKey)
            ->selectRaw('SUM(amount) as paid_amount')
            ->whereIn('payment_status', ['paid', 'completed'])
            ->whereNotNull($foreignKey)
            ->groupBy($foreignKey);
    }

    /**
     * Converts a SQL row into the shape expected by existing Blade templates.
     */
    private function normaliseRow(object $row): array
    {
        $guest = trim(implode(' ', array_filter([
            $row->guest_first_name,
            $row->guest_last_name,
        ])));

        return [
            'type' => $row->type,
            'label' => $row->label,
            'id' => (int) $row->id,
            'organization' => $row->organization ?: 'Corporate account',
            'guest' => $guest !== '' ? $guest : ($row->fallback_guest_name ?: 'Guest'),
            'amount' => max(0, (float) $row->amount),
            'payment_status' => $row->payment_status,
            'status' => $row->status,
            'created_at' => $row->created_at ? Carbon::parse($row->created_at) : null,
        ];
    }

    /**
     * Summarizes a collection for backwards-compatible callers.
     */
    private function summaryFromCollection(Collection $receivables): array
    {
        return [
            'count' => $receivables->count(),
            'total' => (float) $receivables->sum('amount'),
            'organizations' => $receivables->pluck('organization')->unique()->count(),
            'by_type' => $receivables->groupBy('type')->map->count()->all(),
            'by_type_amount' => $receivables->groupBy('type')->map(fn (Collection $items): float => (float) $items->sum('amount'))->all(),
        ];
    }

    /**
     * Checks whether a date field contains an ISO date accepted by the query.
     */
    private function isDate(string $date): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1;
    }
}
