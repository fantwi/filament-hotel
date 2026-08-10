<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\GuestReport;
use App\Models\Guest;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_report_uses_one_selected_period_for_structured_guest_data(): void
    {
        $reportPage = new GuestReport();
        $reportPage->period = 'this_year';

        self::assertSame('This year', $reportPage->periodLabel());

        $report = $reportPage->report();

        self::assertArrayHasKey('payingGuests', $report);
        self::assertArrayHasKey('returningGuests', $report);
        self::assertArrayHasKey('activity', $report);
        self::assertArrayHasKey('food', $report['activity']);
    }

    public function test_guest_report_calculates_paid_guest_spend_for_the_selected_period(): void
    {
        $guest = Guest::query()->create([
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'phone_number' => '0240000000',
            'email' => 'ama@example.test',
        ]);

        Payment::query()->create([
            'guest_id' => $guest->id,
            'amount' => 125.50,
            'method' => 'cash',
            'payment_status' => 'completed',
            'transaction_reference' => 'GUEST-REPORT-TEST',
        ]);

        $report = (new GuestReport())->report();

        self::assertSame(1, $report['payingGuests']);
        self::assertSame(125.5, $report['averageSpend']);
        self::assertSame(125.5, $report['topGuests']->first()->total_spend);
        self::assertSame($guest->id, $report['topGuests']->first()->guest->id);
    }
}
