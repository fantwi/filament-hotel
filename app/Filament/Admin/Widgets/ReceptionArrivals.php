<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Concerns\InteractsWithDashboardDateRange;
use App\Models\Booking;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Provides the reception arrivals Filament dashboard widget.
 */
class ReceptionArrivals extends TableWidget
{
    use InteractsWithDashboardDateRange;

    protected static ?string $heading = "Today's Hotel Arrivals";

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

        return $table->query(Booking::query()->with(['guest', 'room.roomType'])
            ->whereBetween('check_in', [$start->toDateString(), $end->toDateString()])
            ->whereIn('status', ['pending', 'confirmed'])
            ->orderBy('check_in')
            ->orderBy('check_in_time'))
            ->columns([
                TextColumn::make('guest.first_name')->label('Guest')->formatStateUsing(fn (mixed $state, Booking $record): string => trim(($record->guest?->first_name ?? '').' '.($record->guest?->last_name ?? '')) ?: 'Unknown Guest')->searchable(),
                TextColumn::make('room.room_number')->label('Room')->badge(),
                TextColumn::make('room.roomType.name')->label('Room Type'),
                TextColumn::make('check_in_time')->label('Arrival Time'),
                TextColumn::make('payment_status')->badge(),
                TextColumn::make('status')->badge(),
            ])->poll('30s');
    }
}
