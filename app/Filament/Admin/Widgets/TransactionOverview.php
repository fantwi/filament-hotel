<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\BuildsDashboardDrillDowns;
use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Filament\Admin\Pages\BookingCalendar;
use App\Filament\Admin\Pages\CorporateReceivables;
use App\Filament\Admin\Resources\Bookings\BookingResource;
use App\Filament\Admin\Resources\Payments\PaymentResource;
use App\Filament\Admin\Resources\RestaurantOrders\RestaurantOrderResource;
use App\Filament\Admin\Resources\RestaurantReservations\RestaurantReservationResource;
use App\Services\TransactionDashboardSummary;
use Filament\Widgets\Widget;

/**
 * Provides the transaction overview Filament dashboard widget.
 */
class TransactionOverview extends Widget
{
    use BuildsDashboardDrillDowns;
    use InteractsWithDashboardDateRange;

    protected string $view = 'filament.admin.widgets.transaction-overview';

    protected int|string|array $columnSpan = 'full';

    /**
     * Determines whether the current user may view this feature.
     */
    public static function canView(): bool
    {
        return auth()->user()?->can('view transaction dashboard') ?? false;
    }

    /**
     * Builds and returns view data.
     */
    protected function getViewData(): array
    {
        [$start, $end] = $this->dashboardDateRange();
        $summary = app(TransactionDashboardSummary::class)->summarize($start, $end);

        return [
            'rows' => collect($summary['rows'])
                ->map(fn (array $row): array => [
                    ...$row,
                    'url' => $this->channelDrillDownUrl((string) $row['label']),
                ])
                ->all(),
            'periodLabel' => $this->dashboardDateRangeLabel(),
            'totals' => $summary['totals'],
            'links' => [
                'net_collections' => $this->paymentOrSectionUrl('all', 'all', 'collection-performance'),
                'refunds' => $this->paymentOrSectionUrl('refunded', 'all', 'collection-performance'),
                'collection_rate' => $this->transactionDashboardSectionDrillDownUrl('transaction-breakdown'),
                'non_corporate' => $this->transactionDashboardSectionDrillDownUrl('transaction-breakdown'),
                'corporate' => $this->corporateOrSectionUrl('outstanding-follow-up'),
                'overdue_corporate' => $this->corporateOrSectionUrl('outstanding-follow-up'),
            ],
        ];
    }

    /**
     * Resolves the best authorized destination for one transaction channel.
     */
    private function channelDrillDownUrl(string $label): string
    {
        return match ($label) {
            'Hotel bookings' => auth()->check() && BookingResource::canViewAny()
                ? $this->bookingTransactionsDrillDownUrl()
                : $this->transactionDashboardSectionDrillDownUrl('transaction-breakdown'),
            'Conference bookings' => auth()->check() && BookingCalendar::canAccess()
                ? $this->conferenceEventsDrillDownUrl()
                : $this->paymentOrSectionUrl('all', 'conference_bookings', 'transaction-breakdown'),
            'Table reservations' => auth()->check() && RestaurantReservationResource::canViewAny()
                ? $this->restaurantReservationTransactionsDrillDownUrl()
                : $this->paymentOrSectionUrl('all', 'table_reservations', 'transaction-breakdown'),
            'Food orders' => auth()->check() && RestaurantOrderResource::canViewAny()
                ? $this->restaurantOrderDrillDownUrl()
                : $this->paymentOrSectionUrl('all', 'food_orders', 'transaction-breakdown'),
            default => $this->transactionDashboardSectionDrillDownUrl('transaction-breakdown'),
        };
    }

    /**
     * Returns a payment register link or a safe same-page fallback.
     */
    private function paymentOrSectionUrl(string $status, string $transactionType, string $section): string
    {
        return auth()->check() && PaymentResource::canViewAny()
            ? $this->paymentDrillDownUrl($status, $transactionType)
            : $this->transactionDashboardSectionDrillDownUrl($section);
    }

    /**
     * Returns a receivables link or a safe same-page fallback.
     */
    private function corporateOrSectionUrl(string $section): string
    {
        return auth()->check() && CorporateReceivables::canAccess()
            ? $this->corporateReceivablesPeriodDrillDownUrl()
            : $this->transactionDashboardSectionDrillDownUrl($section);
    }
}
