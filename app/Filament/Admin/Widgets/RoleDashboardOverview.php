<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use Filament\Widgets\Widget;

class RoleDashboardOverview extends Widget
{
    use InteractsWithDashboardDateRange;

    protected string $view = 'filament.admin.widgets.role-dashboard-overview';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -10;

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole([
            'super_admin',
            'admin',
            'accountant',
            'manager',
            'receptionist',
        ]) ?? false;
    }

    protected function getViewData(): array
    {
        return [
            'dashboard' => $this->dashboardDetails(),
            'periodLabel' => $this->dashboardDateRangeLabel(),
        ];
    }

    /**
     * @return array{eyebrow: string, title: string, description: string, priorities: array<int, array{title: string, description: string}>}
     */
    private function dashboardDetails(): array
    {
        $user = auth()->user();

        return match (true) {
            $user?->hasRole('super_admin') => [
                'eyebrow' => 'System oversight',
                'title' => 'Super admin command center',
                'description' => 'Review hotel-wide performance, service delivery, inventory signals, and governance information before moving into the detailed reports below.',
                'priorities' => [
                    ['title' => 'Business health', 'description' => 'Review revenue, guest activity, and overall reservation volume.'],
                    ['title' => 'Restaurant performance', 'description' => 'Monitor sales, order flow, and best-selling menu items.'],
                    ['title' => 'Operational risk', 'description' => 'Check kitchen stock and orders that need timely action.'],
                ],
            ],
            $user?->hasRole('admin') => [
                'eyebrow' => 'Hotel administration',
                'title' => 'Admin operations center',
                'description' => 'Keep bookings, service delivery, payments, corporate balances, and kitchen operations aligned for the selected reporting period.',
                'priorities' => [
                    ['title' => 'Service activity', 'description' => 'Review active bookings, events, reservations, and kitchen orders.'],
                    ['title' => 'Financial follow-up', 'description' => 'Monitor payments and corporate balances requiring attention.'],
                    ['title' => 'Kitchen readiness', 'description' => 'Identify stock and order-queue issues before they affect guests.'],
                ],
            ],
            $user?->hasRole('accountant') => [
                'eyebrow' => 'Financial control',
                'title' => 'Accountant finance center',
                'description' => 'Track revenue, recorded payments, refunds, corporate credit, and restaurant performance for the selected reporting period.',
                'priorities' => [
                    ['title' => 'Cash received', 'description' => 'Review completed payments and recent payment references.'],
                    ['title' => 'Open balances', 'description' => 'Follow up on unpaid and corporate-billed transactions.'],
                    ['title' => 'Revenue quality', 'description' => 'Compare restaurant revenue with operational payment activity.'],
                ],
            ],
            $user?->hasRole('manager') => [
                'eyebrow' => 'Operations control',
                'title' => 'Manager operations center',
                'description' => 'Balance guest activity, kitchen production, stock movements, corporate billing, and live service delivery in one view.',
                'priorities' => [
                    ['title' => 'Guest activity', 'description' => 'Review arrivals, events, restaurant activity, and food orders.'],
                    ['title' => 'Kitchen health', 'description' => 'Watch production, stock movement, and the live order queue.'],
                    ['title' => 'Commercial follow-up', 'description' => 'Monitor corporate balances and operational trends.'],
                ],
            ],
            default => [
                'eyebrow' => 'Front desk',
                'title' => 'Reception service center',
                'description' => 'Prepare for arriving and departing guests, upcoming events, and restaurant reservations within the selected reporting period.',
                'priorities' => [
                    ['title' => 'Guest arrivals', 'description' => 'Confirm rooms, arrival times, and payment readiness.'],
                    ['title' => 'Guest departures', 'description' => 'Review check-outs and any final front-desk actions.'],
                    ['title' => 'Venue activity', 'description' => 'Stay prepared for conference and restaurant reservations.'],
                ],
            ],
        };
    }
}
