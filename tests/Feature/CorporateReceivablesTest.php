<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\CorporateReceivables;
use App\Filament\Admin\Widgets\CorporateReceivablesStats;
use App\Models\Booking;
use App\Models\CorporateOrganization;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CorporateReceivablesTest extends TestCase
{
    use RefreshDatabase;

    public function test_mark_paid_route_records_an_offline_payment_for_a_booking(): void
    {
        [$booking, $admin] = $this->bookingFixture();

        $response = $this->actingAs($admin)->post(route('admin.corporate-receivables.pay', [
            'booking',
            $booking->id,
        ]), [
            'method' => 'bank_transfer',
            'transaction_reference' => 'BANK-RECEIVABLE-001',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->id,
            'method' => 'bank_transfer',
            'payment_status' => 'completed',
            'transaction_reference' => 'BANK-RECEIVABLE-001',
        ]);
        self::assertSame('paid', $booking->fresh()->payment_status);
    }

    public function test_receivables_report_shows_outstanding_amounts_and_summary(): void
    {
        [$booking] = $this->bookingFixture();
        Payment::query()->create([
            'booking_id' => $booking->id,
            'guest_id' => $booking->guest_id,
            'amount' => 25,
            'method' => 'cash',
            'payment_status' => 'completed',
        ]);

        $page = new CorporateReceivables;
        $receivables = $page->receivables();

        self::assertSame(75.0, $receivables->first()['amount']);
        self::assertSame(75.0, $page->summary($receivables)['total']);
        self::assertSame(1, $page->summary($receivables)['count']);
    }

    public function test_mark_paid_only_records_the_remaining_balance_after_partial_payment(): void
    {
        [$booking, $admin] = $this->bookingFixture();
        Payment::query()->create([
            'booking_id' => $booking->id,
            'guest_id' => $booking->guest_id,
            'amount' => 25,
            'method' => 'cash',
            'payment_status' => 'completed',
        ]);

        $this->actingAs($admin)->post(route('admin.corporate-receivables.pay', [
            'booking',
            $booking->id,
        ]), ['method' => 'cash']);

        self::assertSame(75.0, (float) Payment::query()->where('booking_id', $booking->id)->latest('id')->value('amount'));
    }

    public function test_receivables_view_uses_responsive_summary_and_cards(): void
    {
        $view = file_get_contents(resource_path('views/filament/admin/pages/corporate-receivables.blade.php'));

        self::assertStringContainsString('Outstanding balance', $view);
        self::assertStringContainsString('lg:grid-cols-4', $view);
        self::assertStringContainsString('md:hidden', $view);
        self::assertStringContainsString('Receivable details', $view);
    }

    public function test_receivables_filter_controls_use_filament_inputs_without_live_query_bindings(): void
    {
        $view = file_get_contents(resource_path('views/filament/admin/pages/corporate-receivables.blade.php'));

        self::assertStringContainsString('<x-filament::input.wrapper', $view);
        self::assertStringContainsString('<x-filament::input.select', $view);
        self::assertStringContainsString('wire:model="draftTransactionType"', $view);
        self::assertStringContainsString('<x-filament::input ', $view);
        self::assertStringContainsString('wire:model="draftSearch"', $view);
        self::assertStringContainsString('wire:model="draftFromDate"', $view);
        self::assertStringContainsString('wire:model="draftUntilDate"', $view);
        self::assertStringNotContainsString('class="fi-input', $view);
        self::assertStringNotContainsString('wire:model.live="search"', $view);
        self::assertStringNotContainsString('wire:model.live="transactionType"', $view);
        self::assertStringNotContainsString('wire:model.live="organizationId"', $view);
        self::assertStringNotContainsString('wire:model.live="fromDate"', $view);
        self::assertStringNotContainsString('wire:model.live="untilDate"', $view);
    }

    public function test_mark_paid_requires_an_accessible_payment_review_before_submission(): void
    {
        $view = file_get_contents(resource_path('views/filament/admin/pages/corporate-receivables.blade.php'));

        self::assertStringContainsString('x-data="corporatePaymentReview()', $view);
        self::assertStringContainsString('x-on:submit.prevent', $view);
        self::assertStringContainsString('Review payment', $view);
        self::assertStringContainsString('Confirm and mark paid', $view);
        self::assertStringContainsString('aria-modal="true"', $view);
        self::assertStringContainsString('Outstanding amount', $view);
    }

    public function test_transactions_section_uses_a_stats_overview_widget(): void
    {
        $view = file_get_contents(resource_path('views/filament/admin/pages/corporate-receivables.blade.php'));

        self::assertTrue(is_subclass_of(CorporateReceivablesStats::class, StatsOverviewWidget::class));
        self::assertStringContainsString('CorporateReceivablesStats::class', $view);
        self::assertStringContainsString('Receivables by service', $view);
    }

    public function test_stats_widget_builds_service_stats_from_grouped_receivables(): void
    {
        $widget = new CorporateReceivablesStats;
        $method = new \ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        $stats = $method->invoke($widget);

        self::assertCount(4, $stats);
    }

    public function test_receivables_queue_is_paginated_and_can_be_filtered(): void
    {
        [$booking] = $this->bookingFixture();

        $page = new CorporateReceivables;
        $page->transactionType = 'booking';
        $page->search = 'Receivables Test';
        $page->perPage = 1;

        $results = $page->paginatedReceivables();

        self::assertInstanceOf(LengthAwarePaginator::class, $results);
        self::assertSame(1, $results->total());
        self::assertCount(1, $results->items());
        self::assertSame('booking', $results->items()[0]['type']);
        self::assertSame($booking->id, $results->items()[0]['id']);
    }

    public function test_receivables_summary_uses_database_aggregates_for_the_full_queue(): void
    {
        $this->bookingFixture();

        $page = new CorporateReceivables;
        $summary = $page->summary();

        self::assertSame(1, $summary['count']);
        self::assertSame(100.0, $summary['total']);
        self::assertSame(1, $summary['organizations']);
        self::assertSame(1, $summary['by_type']['booking']);
        self::assertSame(100.0, $summary['by_type_amount']['booking']);
    }

    #[DataProvider('draftFilters')]
    public function test_draft_receivables_filters_do_not_change_the_summary_until_they_are_applied(string $property, string $value, ?string $companionProperty, ?string $companionValue): void
    {
        $this->bookingFixture();

        $page = new CorporateReceivables;
        $beforeApplying = $page->summary();

        $page->{$property} = $value;

        if ($companionProperty !== null) {
            $page->{$companionProperty} = $companionValue;
        }

        self::assertSame($beforeApplying, $page->summary());

        $page->applyFilters();

        self::assertSame(0, $page->summary()['count']);
    }

    public function test_invalid_draft_date_ranges_leave_the_applied_receivables_query_unchanged(): void
    {
        $this->bookingFixture();

        $page = new CorporateReceivables;
        $page->draftTransactionType = 'conference';
        $page->applyFilters();
        $appliedSummary = $page->summary();

        $page->draftFromDate = '2026-09-20';
        $page->draftUntilDate = '2026-09-10';

        try {
            $page->applyFilters();
            self::fail('An invalid draft date range should not be applied.');
        } catch (ValidationException) {
            self::assertSame('conference', $page->transactionType);
            self::assertSame($appliedSummary, $page->summary());
        }
    }

    public function test_clear_filters_resets_draft_and_applied_receivables_state(): void
    {
        $page = new CorporateReceivables;
        $page->draftTransactionType = 'conference';
        $page->draftSearch = 'unapplied query';
        $page->draftOrganizationId = '999';
        $page->draftFromDate = '2026-09-01';
        $page->draftUntilDate = '2026-09-30';
        $page->applyFilters();

        $page->clearFilters();

        self::assertSame('all', $page->draftTransactionType);
        self::assertSame('', $page->draftSearch);
        self::assertSame('', $page->draftOrganizationId);
        self::assertSame('', $page->draftFromDate);
        self::assertSame('', $page->draftUntilDate);
        self::assertSame('all', $page->transactionType);
        self::assertSame('', $page->search);
        self::assertSame('', $page->organizationId);
        self::assertSame('', $page->fromDate);
        self::assertSame('', $page->untilDate);
        self::assertSame(25, $page->perPage);
    }

    public static function draftFilters(): array
    {
        return [
            'transaction type' => ['draftTransactionType', 'conference', null, null],
            'search' => ['draftSearch', 'No matching receivable', null, null],
            'organization' => ['draftOrganizationId', '999999', null, null],
            'from date' => ['draftFromDate', '2099-01-01', 'draftUntilDate', '2100-01-01'],
            'until date' => ['draftUntilDate', '2000-01-01', 'draftFromDate', '1999-01-01'],
        ];
    }

    /**
     * @return array{0: Booking, 1: User}
     */
    private function bookingFixture(): array
    {
        $organization = CorporateOrganization::query()->create([
            'name' => 'Receivables Test Ltd',
            'credit_limit' => 1000,
            'is_credit_enabled' => true,
        ]);
        $admin = User::factory()->create(['department' => 'accounting']);
        $guestUser = User::factory()->create([
            'department' => 'guest',
            'corporate_organization_id' => $organization->id,
        ]);
        $guest = $guestUser->guest;
        $roomType = RoomType::query()->create([
            'name' => 'Receivables room',
            'price_per_night' => 100,
            'capacity' => 2,
            'description' => 'Receivables test room.',
        ]);
        $room = Room::query()->create([
            'room_type_id' => $roomType->id,
            'room_number' => 'REC-1',
            'status' => 'available',
        ]);
        $booking = Booking::query()->create([
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'corporate_organization_id' => $organization->id,
            'check_in' => today()->addDay(),
            'check_out' => today()->addDays(2),
            'total_price' => 100,
            'status' => 'confirmed',
            'payment_status' => 'pending',
            'hold_status' => 'confirmed',
        ]);

        return [$booking, $admin];
    }
}
