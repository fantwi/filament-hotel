<?php

namespace App\Filament\Admin\Resources\KitchenStockMovements\Tables;

use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class KitchenStockMovementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('occurred_at')->dateTime('M d, Y g:i A')->sortable(),
                TextColumn::make('ingredient.name')->label('Ingredient')->searchable()->sortable(),
                TextColumn::make('type')->badge()->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->title()),
                TextColumn::make('direction')->badge()->color(fn (string $state): string => $state === 'in' ? 'success' : 'danger'),
                TextColumn::make('quantity')->numeric(decimalPlaces: 3),
                TextColumn::make('balance_before')->label('Before')->numeric(decimalPlaces: 3)->toggleable(),
                TextColumn::make('balance_after')->label('After')->numeric(decimalPlaces: 3),
                TextColumn::make('total_cost')->money('GHS')->placeholder('—')->toggleable(),
                TextColumn::make('reference_number')->label('Reference')->placeholder('—')->searchable()->toggleable(),
                TextColumn::make('performedBy.name')->label('Recorded By')->placeholder('System')->toggleable(),
                TextColumn::make('notes')->wrap()->limit(60)->toggleable(),
            ])
            ->filters([
                SelectFilter::make('ingredient_id')->relationship('ingredient', 'name')->searchable()->preload(),
                SelectFilter::make('type')->options(['opening_stock' => 'Opening Stock', 'receipt' => 'Receipt', 'consumption' => 'Consumption', 'wastage' => 'Wastage', 'adjustment_in' => 'Adjustment In', 'adjustment_out' => 'Adjustment Out', 'reversal' => 'Reversal']),
                Filter::make('occurred_at')->schema([DatePicker::make('from'), DatePicker::make('until')])->query(fn (Builder $query, array $data): Builder => $query->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('occurred_at', '>=', $date))->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('occurred_at', '<=', $date))),
            ])
            ->defaultSort('occurred_at', 'desc');
    }
}
