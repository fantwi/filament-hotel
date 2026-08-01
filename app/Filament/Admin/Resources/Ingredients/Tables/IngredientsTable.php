<?php

namespace App\Filament\Admin\Resources\Ingredients\Tables;

use App\Models\Ingredient;
use App\Services\KitchenStockService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class IngredientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('restaurant.name')->label('Restaurant')->toggleable()->visibleFrom('md'),
                TextColumn::make('sku')->label('SKU')->placeholder('No SKU')->searchable()->toggleable(),
                TextColumn::make('category')->placeholder('Uncategorized')->toggleable(),
                TextColumn::make('current_stock')->label('Current Stock')->formatStateUsing(fn (mixed $state, Ingredient $record): string => number_format((float) $state, 3).' '.$record->unit)->sortable(),
                TextColumn::make('reorder_level')->label('Reorder At')->formatStateUsing(fn (mixed $state, Ingredient $record): string => number_format((float) $state, 3).' '.$record->unit)->toggleable(),
                TextColumn::make('stock_status')->label('Status')->state(fn (Ingredient $record): string => (float) $record->current_stock <= 0 ? 'Out of Stock' : ($record->is_low_stock ? 'Low Stock' : 'Healthy'))->badge()->color(fn (string $state): string => match ($state) {
                    'Out of Stock' => 'danger', 'Low Stock' => 'warning', default => 'success'
                }),
                TextColumn::make('unit_cost')->money('GHS')->toggleable(),
                TextColumn::make('stock_value')->label('Stock Value')->state(fn (Ingredient $record): float => $record->stock_value)->money('GHS')->toggleable(),
                IconColumn::make('is_active')->boolean()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('restaurant_id')->relationship('restaurant', 'name')->searchable()->preload(),
                SelectFilter::make('category')->options(fn (): array => Ingredient::query()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category', 'category')->all()),
                Filter::make('low_stock')->label('Low Stock Only')->query(fn (Builder $query): Builder => $query->whereColumn('current_stock', '<=', 'reorder_level')),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('receive_stock')->label('Receive Stock')->icon('heroicon-o-arrow-down-tray')->color('success')
                        ->visible(fn (): bool => auth()->user()?->can('manage kitchen stock') ?? false)
                        ->schema([TextInput::make('quantity')->numeric()->minValue(0.001)->step(0.001)->required(), TextInput::make('unit_cost')->label('Purchase Unit Cost')->numeric()->prefix('GHS')->minValue(0)->step(0.01), TextInput::make('reference_number')->label('Invoice / Delivery Reference'), Textarea::make('notes')->rows(3)])
                        ->action(function (Ingredient $record, array $data, KitchenStockService $stock): void {
                            $stock->receive($record, (float) $data['quantity'], filled($data['unit_cost'] ?? null) ? (float) $data['unit_cost'] : null, $data['reference_number'] ?? null, $data['notes'] ?? null);
                            Notification::make()->title('Stock received')->success()->send();
                        }),
                    Action::make('record_wastage')->label('Record Wastage')->icon('heroicon-o-trash')->color('danger')
                        ->visible(fn (): bool => auth()->user()?->can('manage kitchen stock') ?? false)
                        ->schema([TextInput::make('quantity')->numeric()->minValue(0.001)->step(0.001)->required(), Textarea::make('notes')->label('Reason for Wastage')->required()->rows(3)])
                        ->requiresConfirmation()
                        ->action(function (Ingredient $record, array $data, KitchenStockService $stock): void {
                            $stock->recordWastage($record, (float) $data['quantity'], $data['notes']);
                            Notification::make()->title('Wastage recorded')->warning()->send();
                        }),
                    Action::make('stock_count')->label('Enter Stock Count')->icon('heroicon-o-clipboard-document-check')->color('info')
                        ->visible(fn (): bool => auth()->user()?->can('manage kitchen stock') ?? false)
                        ->schema([TextInput::make('counted_quantity')->label('Physically Counted Quantity')->numeric()->minValue(0)->step(0.001)->required(), Textarea::make('notes')->label('Adjustment Reason')->required()->rows(3)])
                        ->requiresConfirmation()
                        ->action(function (Ingredient $record, array $data, KitchenStockService $stock): void {
                            $movement = $stock->adjustToCountedStock($record, (float) $data['counted_quantity'], $data['notes']);
                            Notification::make()->title($movement ? 'Stock adjusted' : 'No stock adjustment required')->success()->send();
                        }),
                ])->label('Stock Actions')->button(),
            ])
            ->defaultSort('name');
    }
}
