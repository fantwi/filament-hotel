<?php

namespace App\Filament\Admin\Pages;

use App\Models\Guest;
use App\Models\Payment;
use Filament\Pages\Page;

class GuestReport extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Guest Report';

    protected string $view = 'filament.admin.pages.guest-report';

    public function report(): array
    {
        $topGuests = Guest::query()
            ->withCount('bookings')
            ->orderByDesc('bookings_count')
            ->limit(5)
            ->get(['id', 'first_name', 'last_name', 'email']);

        return [
            'total' => Guest::count(),
            'newThisMonth' => Guest::whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->count(),
            'returning' => Guest::has('bookings', '>=', 2)->count(),
            'averageSpend' => Payment::whereNotNull('guest_id')->avg('amount') ?? 0,
            'topGuests' => $topGuests,
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'accountant']) ?? false;
    }
}
