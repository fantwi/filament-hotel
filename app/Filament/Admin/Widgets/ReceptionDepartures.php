<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Models\Booking;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

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
                    ->searchable(),
                TextColumn::make('room.room_number')->label('Room')->badge(),
                TextColumn::make('room.roomType.name')->label('Room Type'),
                TextColumn::make('check_out')->label('Departure Date')->date('M d, Y'),
                TextColumn::make('check_out_time')->label('Departure Time'),
                TextColumn::make('payment_status')->badge(),
                TextColumn::make('status')->badge(),
            ]);
    }

    /**
     * Determines whether the current user may view this feature.
     */
    public static function canView(): bool
    {
        return auth()->user()?->can('view reception dashboard') ?? false;
    }
}
