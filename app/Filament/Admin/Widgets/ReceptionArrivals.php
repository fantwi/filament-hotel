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
 * Provides the reception arrivals Filament dashboard widget.
 */
class ReceptionArrivals extends TableWidget
{
    use InteractsWithDashboardDateRange;

    protected int|string|array $columnSpan = 'full';

    /**
     * Determines whether the current user may view this feature.
     */
    public static function canView(): bool
    {
        return auth()->user()?->can('view reception dashboard') ?? false;
    }

    /**
     * Configures the table data source, columns, and actions.
     */
    public function table(Table $table): Table
    {
        [$start, $end] = $this->dashboardDateRange();

        return $table
            ->heading('Hotel Arrivals')
            ->description('Scheduled arrivals for '.$this->dashboardDateRangeLabel())
            ->query(Booking::query()->with(['guest', 'room.roomType'])
                ->whereBetween('check_in', [$start->toDateString(), $end->toDateString()])
                ->whereIn('status', ['pending', 'confirmed'])
                ->orderBy('check_in')
                ->orderBy('check_in_time'))
            ->columns([
                TextColumn::make('guest.first_name')
                    ->label('Guest')
                    ->formatStateUsing(fn (mixed $state, Booking $record): string => trim(($record->guest?->first_name ?? '').' '.($record->guest?->last_name ?? '')) ?: 'Unknown Guest')
                    ->description(fn (Booking $record): string => 'Arrives '.$record->check_in->format('M j, Y').' at '.(filled($record->check_in_time) ? Carbon::parse($record->check_in_time)->format('g:i A') : 'time not set'))
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
                TextColumn::make('check_in')->label('Arrival Date')->date('M j, Y')->toggleable()->visibleFrom('md'),
                TextColumn::make('check_in_time')->label('Arrival Time')->time('g:i A')->placeholder('Not set')->toggleable()->visibleFrom('md'),
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
            ->emptyStateHeading('No arrivals in this period')
            ->emptyStateDescription('Choose another dashboard period or create a hotel booking.')
            ->emptyStateIcon('heroicon-o-calendar-days')
            ->defaultPaginationPageOption(10)
            ->poll($start->lte(today()) && $end->gte(today()) ? '30s' : null);
    }
}
