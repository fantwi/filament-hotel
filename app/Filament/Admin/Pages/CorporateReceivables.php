<?php

namespace App\Filament\Admin\Pages;

use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\RestaurantOrder;
use App\Models\RestaurantReservation;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

/**
 * Provides the corporate receivables Filament administration page.
 */
class CorporateReceivables extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|\UnitEnum|null $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Corporate Receivables';

    protected static ?int $navigationSort = 11;

    protected string $view = 'filament.admin.pages.corporate-receivables';

    /**
     * Determines whether the current user may access this feature.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'accountant']) ?? false;
    }

    /**
     * Configures receivables for the Filament administration interface.
     */
    public function receivables(): Collection
    {
        $toRows = static function (Collection $records, string $type, string $label, string $amountField): Collection {
            return $records
                ->map(function ($record) use ($type, $label, $amountField): array {
                    $gross = (float) $record->{$amountField};
                    $paid = (float) $record->payments
                        ->whereIn('payment_status', ['paid', 'completed'])
                        ->sum('amount');

                    return [
                        'type' => $type,
                        'label' => $label,
                        'id' => $record->id,
                        'organization' => $record->corporateOrganization?->name ?? 'Corporate account',
                        'guest' => $record->guest?->full_name ?? $record->guest?->email ?? 'Guest',
                        'amount' => max(0, $gross - $paid),
                        'payment_status' => $record->payment_status,
                        'status' => $record->status,
                        'created_at' => $record->created_at,
                    ];
                })
                ->filter(fn (array $row): bool => $row['amount'] > 0)
                ->values();
        };

        return $toRows(Booking::query()->with(['guest', 'corporateOrganization', 'payments'])->whereNotNull('corporate_organization_id')->whereNotIn('payment_status', ['paid', 'completed', 'refunded'])->whereNotIn('status', ['cancelled', 'no_show', 'expired'])->get(), 'booking', 'Room booking', 'total_price')
            ->concat($toRows(ConferenceBooking::query()->with(['guest', 'corporateOrganization', 'payments'])->whereNotNull('corporate_organization_id')->whereNotIn('payment_status', ['paid', 'completed', 'refunded'])->whereNotIn('status', ['cancelled', 'no_show', 'expired'])->get(), 'conference', 'Conference booking', 'total_price'))
            ->concat($toRows(RestaurantReservation::query()->with(['guest', 'corporateOrganization', 'payments'])->whereNotNull('corporate_organization_id')->whereNotIn('payment_status', ['paid', 'completed', 'refunded'])->whereNotIn('status', ['cancelled', 'no_show', 'expired'])->get(), 'reservation', 'Table reservation', 'reservation_fee'))
            ->concat($toRows(RestaurantOrder::query()->with(['guest', 'corporateOrganization', 'payments'])->whereNotNull('corporate_organization_id')->whereNotIn('payment_status', ['paid', 'completed', 'refunded'])->whereNotIn('status', ['cancelled', 'expired'])->get(), 'order', 'Food order', 'total'))
            ->sortByDesc('created_at')
            ->values();
    }

    /**
     * Summarizes the currently outstanding corporate transactions.
     */
    public function summary(?Collection $receivables = null): array
    {
        $receivables ??= $this->receivables();

        return [
            'count' => $receivables->count(),
            'total' => (float) $receivables->sum('amount'),
            'organizations' => $receivables->pluck('organization')->unique()->count(),
            'by_type' => $receivables->groupBy('type')->map->count()->all(),
        ];
    }
}
