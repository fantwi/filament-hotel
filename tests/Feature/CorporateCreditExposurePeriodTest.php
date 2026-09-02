<?php

namespace Tests\Feature;

use App\Filament\Admin\Widgets\AdminFinanceStats;
use App\Models\Booking;
use App\Models\CorporateOrganization;
use App\Models\Guest;
use App\Models\Room;
use App\Models\RoomType;
use App\Services\CorporateCreditService;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class CorporateCreditExposurePeriodTest extends TestCase
{
    use RefreshDatabase;

    public function test_credit_exposure_uses_all_unpaid_transactions_while_period_metrics_stay_date_filtered(): void
    {
        $organization = $this->corporateAccountWithOutstandingBookings();

        $overview = app(CorporateCreditService::class)->dashboardOverview(
            Carbon::parse('2026-08-01')->startOfDay(),
            Carbon::parse('2026-08-31')->endOfDay(),
        );

        self::assertSame(700.0, (float) $overview['outstanding']);
        self::assertSame(300.0, (float) $overview['available_credit']);
        self::assertArrayHasKey('billed_in_period', $overview);
        self::assertArrayHasKey('period_outstanding', $overview);
        self::assertSame(100.0, (float) $overview['billed_in_period']);
        self::assertSame(100.0, (float) $overview['period_outstanding']);
        self::assertSame(700.0, (float) $overview['accounts']->firstWhere('id', $organization->id)['outstanding']);
        self::assertSame(300.0, (float) $overview['accounts']->firstWhere('id', $organization->id)['available_credit']);
    }

    public function test_admin_finance_stat_presents_corporate_outstanding_as_a_current_balance(): void
    {
        $this->corporateAccountWithOutstandingBookings();

        $stats = $this->statsFor('2026-08-01', '2026-08-31');

        self::assertSame('GHS 700.00', $stats['Corporate Outstanding']->getValue());
        self::assertSame('Current unpaid balance across all periods', $stats['Corporate Outstanding']->getDescription());
    }

    private function corporateAccountWithOutstandingBookings(): CorporateOrganization
    {
        $organization = CorporateOrganization::query()->create([
            'name' => 'Exposure Test Ltd',
            'credit_limit' => 1000,
            'is_credit_enabled' => true,
        ]);
        $guest = Guest::query()->create([
            'first_name' => 'Corporate',
            'last_name' => 'Exposure',
            'email' => 'corporate-exposure@example.test',
            'phone_number' => '0240000000',
        ]);
        $roomType = RoomType::query()->create([
            'name' => 'Corporate Exposure Room',
            'price_per_night' => 100,
            'capacity' => 2,
        ]);
        $room = Room::query()->create([
            'room_type_id' => $roomType->id,
            'room_number' => 'EXPOSURE-1',
            'status' => 'available',
        ]);

        $this->booking($organization, $guest, $room, 600, '2026-07-15 09:00:00');
        $this->booking($organization, $guest, $room, 100, '2026-08-15 09:00:00');

        return $organization;
    }

    private function booking(
        CorporateOrganization $organization,
        Guest $guest,
        Room $room,
        float $total,
        string $createdAt,
    ): void {
        $booking = Booking::query()->create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'corporate_organization_id' => $organization->id,
            'check_in' => '2026-09-10',
            'check_out' => '2026-09-11',
            'total_price' => $total,
            'status' => 'confirmed',
            'payment_status' => 'pending',
        ]);

        $booking->forceFill([
            'created_at' => Carbon::parse($createdAt),
            'updated_at' => Carbon::parse($createdAt),
        ])->saveQuietly();
    }

    /**
     * @return array<string, Stat>
     */
    private function statsFor(string $startDate, string $endDate): array
    {
        $widget = new AdminFinanceStats;
        $widget->pageFilters = [
            'period' => 'custom',
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        $method = new ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        return collect($method->invoke($widget))
            ->mapWithKeys(fn (Stat $stat): array => [(string) $stat->getLabel() => $stat])
            ->all();
    }
}
