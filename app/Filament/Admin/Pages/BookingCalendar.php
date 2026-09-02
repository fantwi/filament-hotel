<?php

namespace App\Filament\Admin\Pages;

use Carbon\Carbon;
use Filament\Pages\Page;

/**
 * Provides the booking calendar Filament administration page.
 */
class BookingCalendar extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Booking Calendar';

    protected static ?string $title = 'Booking Calendar';

    protected static string|\UnitEnum|null $navigationGroup = 'Accommodation';

    protected static ?int $navigationSort = 50;

    protected string $view = 'filament.admin.pages.booking-calendar';

    public string $eventType = 'all';

    public string $statusScope = 'all';

    public string $focusDate = '';

    public string $filterEndDate = '';

    /**
     * Applies an optional dashboard drill-down scope to the calendar.
     */
    public function mount(): void
    {
        $type = (string) request()->query('type', 'all');
        $statusScope = (string) request()->query('status_scope', 'all');
        $startDate = (string) request()->query('start_date', '');
        $endDate = (string) request()->query('end_date', '');

        $this->eventType = in_array($type, ['all', 'hotel', 'conference', 'restaurant'], true)
            ? $type
            : 'all';
        $this->statusScope = in_array($statusScope, ['all', 'active'], true)
            ? $statusScope
            : 'all';
        $this->focusDate = $this->isIsoDate($startDate) ? $startDate : '';
        $this->filterEndDate = $this->isIsoDate($endDate) ? $endDate : '';
    }

    /**
     * Describes the applied dashboard scope in plain language.
     */
    public function focusedScopeLabel(): string
    {
        $type = match ($this->eventType) {
            'hotel' => 'hotel bookings',
            'conference' => 'conference bookings',
            'restaurant' => 'table reservations',
            default => 'reservations',
        };

        return ucfirst(($this->statusScope === 'active' ? 'active ' : '').$type);
    }

    /**
     * Formats the dashboard date range displayed above the calendar.
     */
    public function focusedPeriodLabel(): ?string
    {
        if ($this->focusDate === '') {
            return null;
        }

        $start = Carbon::parse($this->focusDate);

        if ($this->filterEndDate === '') {
            return $start->format('M j, Y');
        }

        return $start->format('M j, Y').' – '.Carbon::parse($this->filterEndDate)->format('M j, Y');
    }

    /**
     * Explains the three reservation sources shown on the calendar.
     */
    public function getSubheading(): ?string
    {
        return 'See hotel stays, conference bookings, and restaurant reservations together.';
    }

    /**
     * Determines whether the current user may access this feature.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole([
            'super_admin',
            'admin',
            'manager',
            'accountant',
            'receptionist',
        ]) ?? false;
    }

    /**
     * Checks whether a calendar focus value is a real ISO date.
     */
    private function isIsoDate(string $date): bool
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $parts) !== 1) {
            return false;
        }

        return checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]);
    }
}
