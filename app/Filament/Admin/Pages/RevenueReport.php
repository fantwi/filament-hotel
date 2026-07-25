<?php

namespace App\Filament\Admin\Pages;

use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\Payment;
use App\Models\RestaurantReservation;
use Filament\Pages\Page;

class RevenueReport extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|\UnitEnum|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Revenue Report';

    protected static ?int $navigationSort = 30;

    protected string $view = 'filament.admin.pages.revenue-report';

    public function report(): array
    {
        $paid = Payment::query()->whereIn('payment_status', ['paid', 'completed']);

        return [
            'daily' => (clone $paid)->whereDate('created_at', today())->sum('amount'),
            'weekly' => (clone $paid)->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->sum('amount'),
            'monthly' => (clone $paid)->whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->sum('amount'),
            'annual' => (clone $paid)->whereYear('created_at', now()->year)->sum('amount'),
            'refunds' => Payment::where('payment_status', 'refunded')->sum('amount'),
            'outstanding' => Booking::whereIn('payment_status', ['pending', 'unpaid'])->sum('total_price')
                + ConferenceBooking::whereIn('payment_status', ['pending', 'unpaid'])->sum('total_price')
                + RestaurantReservation::where('payment_status', 'pending')->sum('reservation_fee'),
            'methods' => (clone $paid)->selectRaw('method, SUM(amount) as total')->groupBy('method')->pluck('total', 'method'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'accountant']) ?? false;
    }
}
