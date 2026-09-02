<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Filament\Admin\Resources\Bookings\BookingResource;
use App\Models\Booking;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Provides the reception departures Filament dashboard widget.
 */
class ReceptionDepartures extends TableWidget
{
    use InteractsWithDashboardDateRange;

    protected int|string|array $columnSpan = 'full';

    /**
     * Configures the selected-period departure queue.
     */
    public function table(Table $table): Table
    {
        [$start, $end] = $this->dashboardDateRange();

        return $table
            ->heading('Hotel Departures')
            ->description('Scheduled departures for '.$this->dashboardDateRangeLabel())
            ->query(Booking::query()
                ->with(['guest', 'room.roomType'])
                ->whereBetween('check_out', [$start->toDateString(), $end->toDateString()])
                ->whereIn('status', ['confirmed', 'checked_in'])
                ->orderBy('check_out')
                ->orderBy('check_out_time'))
            ->columns([
                TextColumn::make('guest.first_name')
                    ->label('Guest')
                    ->formatStateUsing(fn (mixed $state, Booking $record): string => trim(($record->guest?->first_name ?? '').' '.($record->guest?->last_name ?? '')) ?: 'Unknown Guest')
                    ->description(fn (Booking $record): string => 'Departs '.$record->check_out->format('M j, Y').' at '.(filled($record->check_out_time) ? Carbon::parse($record->check_out_time)->format('g:i A') : 'time not set'))
                    ->wrap()
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas(
                        'guest',
                        fn (Builder $guestQuery): Builder => $guestQuery->where(
                            fn (Builder $nameQuery): Builder => $nameQuery
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%"),
                        ),
                    )),
                TextColumn::make('room.room_number')->label('Room')->badge(),
                TextColumn::make('room.roomType.name')->label('Room Type')->toggleable()->visibleFrom('lg'),
                TextColumn::make('check_out')->label('Departure Date')->date('M d, Y')->toggleable()->visibleFrom('md'),
                TextColumn::make('check_out_time')->label('Departure Time')->time('g:i A')->placeholder('Not set')->toggleable()->visibleFrom('md'),
                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => str($state ?? 'unknown')->replace('_', ' ')->headline()->toString())
                    ->color(fn (?string $state): string => match ($state) {
                        'paid', 'completed' => 'success',
                        'pending', 'partial', 'partially_paid' => 'warning',
                        'unpaid', 'failed', 'cancelled', 'expired' => 'danger',
                        'refunded', 'refund' => 'info',
                        default => 'gray',
                    })
                    ->toggleable()
                    ->visibleFrom('md'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => str($state ?? 'unknown')->replace('_', ' ')->headline()->toString())
                    ->color(fn (?string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'info',
                        'checked_in' => 'success',
                        'cancelled', 'expired', 'no_show' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->recordActions([
                Action::make('details')
                    ->label('Details')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (Booking $record): string => BookingResource::getUrl('view', ['record' => $record])),
            ])
            ->emptyStateHeading('No departures in this period')
            ->emptyStateDescription('Choose another dashboard period to review scheduled hotel departures.')
            ->emptyStateIcon('heroicon-o-calendar-days')
            ->defaultPaginationPageOption(10)
            ->poll($start->lte(today()) && $end->gte(today()) ? '30s' : null);
    }

    /**
     * Determines whether the current user may view this feature.
     */
    public static function canView(): bool
    {
        return auth()->user()?->can('view reception dashboard') ?? false;
    }
}
