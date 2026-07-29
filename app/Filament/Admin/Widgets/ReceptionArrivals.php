<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Booking;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ReceptionArrivals extends TableWidget
{
    protected static ?string $heading = "Today's Hotel Arrivals";

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('view reception dashboard') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table->query(Booking::query()->with(['guest', 'room.roomType'])
            ->whereDate('check_in', today())->whereIn('status', ['pending', 'confirmed'])->orderBy('check_in_time'))
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
