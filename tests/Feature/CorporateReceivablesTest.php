<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\CorporateReceivables;
use App\Models\Booking;
use App\Models\CorporateOrganization;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
