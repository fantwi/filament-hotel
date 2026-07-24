<?php

namespace App\Filament\Admin\Widgets;

use App\Models\MenuItem;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class BestSellingMenuItems extends TableWidget
{
    protected static ?string $heading = 'Best-Selling Menu Items';

    protected static ?int $sort = 40;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                MenuItem::query()
                    ->with('category')
                    ->withSum([
                        'orderItems as total_quantity_sold' => fn (Builder $query) => $query->whereHas(
                            'order',
                            fn (Builder $orderQuery) => $orderQuery->where('payment_status', 'completed'),
                        ),
                    ], 'quantity')
                    ->withSum([
                        'orderItems as total_sales_value' => fn (Builder $query) => $query->whereHas(
                            'order',
                            fn (Builder $orderQuery) => $orderQuery->where('payment_status', 'completed'),
                        ),
                    ], 'total_price')
                    ->orderByDesc('total_quantity_sold'),
            )
            ->columns([
                ImageColumn::make('image')->disk('public')->square(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('category.name')->label('Category')->sortable(),
                TextColumn::make('total_quantity_sold')
                    ->label('Quantity Sold')
                    ->numeric()
                    ->default(0)
                    ->sortable(),
                TextColumn::make('total_sales_value')
                    ->label('Sales Value')
                    ->money('GHS')
                    ->default(0)
                    ->sortable(),
                TextColumn::make('price')->money('GHS')->sortable(),
            ])
            ->defaultPaginationPageOption(5);
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'accountant']) ?? false;
    }
}
