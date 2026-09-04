<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Concerns\InteractsWithReportPeriod;
use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\RestaurantReservation;
use App\Models\Room;
use App\Services\CurrentVenueAvailability;
use App\Support\Reporting\OccupancyReportCsv;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Provides the occupancy report Filament administration page.
 */
class OccupancyReport extends Page
{
    use InteractsWithReportPeriod;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|\UnitEnum|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Occupancy Report';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.admin.pages.occupancy-report';

    /**
     * Makes the applied occupancy report available outside the dashboard.
     *
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportCsv')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn (): StreamedResponse => $this->exportCsv()),
        ];
    }

    /**
     * Streams the currently applied report range as a CSV download.
     */
    public function exportCsv(): StreamedResponse
    {
        abort_unless(static::canAccess(), 403);

        $report = $this->report();
        $csv = app(OccupancyReportCsv::class)->toCsv($report, $this->periodLabel());
        $filename = sprintf(
            'occupancy-report-%s-to-%s.csv',
            $report['periodStart']->toDateString(),
            $report['periodEnd']->toDateString(),
        );

        return response()->streamDownload(
            static function () use ($csv): void {
                echo $csv;
            },
            $filename,
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    /**
     * Configures report for the Filament administration interface.
     */
    public function report(): array
    {
        [$periodStart, $periodEnd] = $this->periodBounds();
        $periodEndExclusive = $periodEnd->copy()->addDay()->startOfDay();
        $snapshotAt = now();

        $roomStatus = [
            'total' => Room::count(),
            'occupied' => Room::where('status', 'occupied')->count(),
            'available' => Room::where('status', 'available')->count(),
            'maintenance' => Room::where('status', 'maintenance')->count(),
        ];

        $roomsInService = $roomStatus['total'] - $roomStatus['maintenance'];

        $hotelBookings = Booking::query()
            ->where('check_in', '<', $periodEndExclusive->toDateString())
            ->where('check_out', '>', $periodStart->toDateString())
            ->whereNotIn('status', ['cancelled', 'expired', 'no_show'])
            ->count();

        $occupancyTrend = $this->occupancyTrend(
            $periodStart,
            $periodEndExclusive,
            $roomStatus['total'],
            $roomsInService,
        );
        $bookedRoomNights = array_sum($occupancyTrend['bookedRoomNights']);
        $roomNightCapacity = array_sum($occupancyTrend['roomNightCapacities']);

        $conferenceBookings = ConferenceBooking::query()
            ->whereBetween('booking_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->count();

        $tableReservations = RestaurantReservation::query()
            ->whereBetween('reservation_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->count();

        $hasScheduledActivity = $hotelBookings > 0
            || $conferenceBookings > 0
            || $tableReservations > 0;

        $conferenceAvailability = app(CurrentVenueAvailability::class)->conferenceRooms($snapshotAt);

        $tableStatus = app(CurrentVenueAvailability::class)->restaurantTables($snapshotAt);

        return [
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
            'snapshotAt' => $snapshotAt,
            'roomStatus' => $roomStatus,
            'roomsInService' => $roomsInService,
            'hotelBookings' => $hotelBookings,
            'bookedRoomNights' => $bookedRoomNights,
            'roomNightCapacity' => $roomNightCapacity,
            'occupancyRate' => $roomNightCapacity > 0 ? ($bookedRoomNights / $roomNightCapacity) * 100 : null,
            'occupancyTrend' => $occupancyTrend,
            'conferenceBookings' => $conferenceBookings,
            'tableReservations' => $tableReservations,
            'hasScheduledActivity' => $hasScheduledActivity,
            'conferenceAvailability' => $conferenceAvailability,
            'tableStatus' => $tableStatus,
        ];
    }

    /**
     * Opens the unified calendar at the selected period and reservation channel.
     */
    public function reservationCalendarUrl(string $type): string
    {
        [$periodStart, $periodEnd] = $this->periodBounds();

        return BookingCalendar::getUrl([
            'type' => $type,
            'status_scope' => 'reportable',
            'start_date' => $periodStart->toDateString(),
            'end_date' => $periodEnd->toDateString(),
        ]);
    }

    /**
     * Streams overlapping stays into readable date buckets and calculates their capacity.
     *
     * @return array{
     *     granularity: string,
     *     labels: list<string>,
     *     bookedRoomNights: list<int>,
     *     roomNightCapacities: list<int>,
     *     occupancyRates: list<float|null>
     * }
     */
    private function occupancyTrend(
        Carbon $periodStart,
        Carbon $periodEnd,
        int $totalRooms,
        int $currentRoomsInService,
    ): array {
        $granularity = $this->occupancyTrendGranularity($periodStart, $periodEnd);
        $buckets = [];
        $cursor = $periodStart->copy()->startOfDay();

        while ($cursor->lessThan($periodEnd)) {
            $bucketStart = $cursor->copy();
            $nextBoundary = match ($granularity) {
                'day' => $cursor->copy()->addDay(),
                'week' => $cursor->copy()->startOfWeek()->addWeek(),
                'month' => $cursor->copy()->startOfMonth()->addMonth(),
                default => $cursor->copy()->startOfYear()->addYear(),
            };
            $bucketEnd = $nextBoundary->lessThan($periodEnd) ? $nextBoundary : $periodEnd->copy();

            $buckets[] = [
                'start' => $bucketStart,
                'end' => $bucketEnd,
                'label' => $this->occupancyTrendLabel($bucketStart, $bucketEnd, $granularity),
                'bookedRoomNights' => 0,
                'roomNightCapacity' => $this->roomNightCapacity(
                    $bucketStart,
                    $bucketEnd,
                    $totalRooms,
                    $currentRoomsInService,
                ),
            ];

            $cursor = $bucketEnd->copy();
        }

        Booking::query()
            ->where('check_in', '<', $periodEnd->toDateString())
            ->where('check_out', '>', $periodStart->toDateString())
            ->whereNotIn('status', ['cancelled', 'expired', 'no_show'])
            ->select(['id', 'check_in', 'check_out'])
            ->lazyById()
            ->each(function (Booking $booking) use (&$buckets, $periodStart, $periodEnd): void {
                $checkIn = Carbon::parse($booking->check_in)->max($periodStart);
                $checkOut = Carbon::parse($booking->check_out)->min($periodEnd);

                foreach ($buckets as &$bucket) {
                    $overlapStart = $checkIn->greaterThan($bucket['start']) ? $checkIn : $bucket['start'];
                    $overlapEnd = $checkOut->lessThan($bucket['end']) ? $checkOut : $bucket['end'];

                    if ($overlapEnd->greaterThan($overlapStart)) {
                        $bucket['bookedRoomNights'] += (int) $overlapStart->diffInDays($overlapEnd);
                    }
                }

                unset($bucket);
            });

        return [
            'granularity' => $granularity,
            'labels' => array_column($buckets, 'label'),
            'bookedRoomNights' => array_column($buckets, 'bookedRoomNights'),
            'roomNightCapacities' => array_column($buckets, 'roomNightCapacity'),
            'occupancyRates' => array_map(
                fn (array $bucket): ?float => $bucket['roomNightCapacity'] > 0
                    ? ($bucket['bookedRoomNights'] / $bucket['roomNightCapacity']) * 100
                    : null,
                $buckets,
            ),
        ];
    }

    /**
     * Selects a chart resolution that remains useful on small screens.
     */
    private function occupancyTrendGranularity(Carbon $periodStart, Carbon $periodEnd): string
    {
        $days = (int) $periodStart->copy()->startOfDay()->diffInDays($periodEnd->copy()->startOfDay());

        return match (true) {
            $days <= 62 => 'day',
            $days <= 183 => 'week',
            $days <= 730 => 'month',
            default => 'year',
        };
    }

    /**
     * Formats each occupancy bucket without hiding partial weeks.
     */
    private function occupancyTrendLabel(Carbon $bucketStart, Carbon $bucketEnd, string $granularity): string
    {
        return match ($granularity) {
            'day' => $bucketStart->format('M j'),
            'week' => $bucketStart->format('M j').' - '.$bucketEnd->copy()->subDay()->format('M j'),
            'month' => $bucketStart->format('M Y'),
            default => $bucketStart->format('Y'),
        };
    }

    /**
     * Calculates capacity without projecting today's maintenance state
     * backwards onto completed historical dates.
     */
    private function roomNightCapacity(
        Carbon $periodStart,
        Carbon $periodEnd,
        int $totalRooms,
        int $currentRoomsInService,
    ): int {
        $today = now()->startOfDay();
        $historicalDays = 0;
        $currentAndFutureDays = 0;

        if ($periodStart->lessThan($today)) {
            $historicalEnd = $periodEnd->lessThan($today)
                ? $periodEnd
                : $today;

            if ($historicalEnd->greaterThan($periodStart)) {
                $historicalDays = (int) $periodStart->diffInDays($historicalEnd);
            }
        }

        if ($periodEnd->greaterThan($today)) {
            $currentStart = $periodStart->greaterThan($today)
                ? $periodStart
                : $today;

            if ($periodEnd->greaterThan($currentStart)) {
                $currentAndFutureDays = (int) $currentStart->diffInDays($periodEnd);
            }
        }

        return ($totalRooms * $historicalDays)
            + ($currentRoomsInService * $currentAndFutureDays);
    }

    /**
     * Determines whether the current user may access this feature.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'receptionist']) ?? false;
    }
}
