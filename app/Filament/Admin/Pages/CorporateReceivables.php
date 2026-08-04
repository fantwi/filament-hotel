<?php

namespace App\Filament\Admin\Pages;

use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\RestaurantOrder;
use App\Models\RestaurantReservation;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class CorporateReceivables extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|\UnitEnum|null $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Corporate Receivables';

    protected static ?int $navigationSort = 11;

    protected string $view = 'filament.admin.pages.corporate-receivables';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'accountant']) ?? false;
    }

    public function receivables(): Collection
    {
        $toRows = static function (Collection $records, string $type, string $label, string $amountField): Collection {
            return $records->map(fn ($record): array => [
                'type' => $type,
                'label' => $label,
                'id' => $record->id,
                'organization' => $record->corporateOrganization?->name ?? 'Corporate account',
                'guest' => $record->guest?->full_name ?? $record->guest?->email ?? 'Guest',
                'amount' => (float) $record->{$amountField},
                'created_at' => $record->created_at,
            ]);
        };

        return $toRows(Booking::query()->with(['guest', 'corporateOrganization'])->whereNotNull('corporate_organization_id')->where('payment_status', '!=', 'paid')->whereNotIn('status', ['cancelled', 'no_show', 'expired'])->get(), 'booking', 'Room booking', 'total_price')
            ->concat($toRows(ConferenceBooking::query()->with(['guest', 'corporateOrganization'])->whereNotNull('corporate_organization_id')->where('payment_status', '!=', 'paid')->where('status', '!=', 'cancelled')->get(), 'conference', 'Conference booking', 'total_price'))
            ->concat($toRows(RestaurantReservation::query()->with(['guest', 'corporateOrganization'])->whereNotNull('corporate_organization_id')->where('payment_status', '!=', 'completed')->whereNotIn('status', ['cancelled', 'no_show'])->get(), 'reservation', 'Table reservation', 'reservation_fee'))
            ->concat($toRows(RestaurantOrder::query()->with(['guest', 'corporateOrganization'])->whereNotNull('corporate_organization_id')->where('payment_status', '!=', 'completed')->where('status', '!=', 'cancelled')->get(), 'order', 'Food order', 'total'))
            ->sortByDesc('created_at')
            ->values();
    }
}
