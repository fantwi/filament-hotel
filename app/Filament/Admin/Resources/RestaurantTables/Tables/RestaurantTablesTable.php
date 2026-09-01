<?php

namespace App\Filament\Admin\Resources\RestaurantTables\Tables;

use App\Filament\Admin\Resources\RestaurantTables\RestaurantTableResource;
use App\Models\RestaurantTable;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Configures Filament administration for restaurant tables table.
 */
class RestaurantTablesTable
{
    /**
     * Configures configure for the Filament administration interface.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No restaurant tables found')
            ->emptyStateDescription('Create a restaurant table or reset filters to manage dining capacity.')
            ->emptyStateIcon('heroicon-o-table-cells')
            ->emptyStateActions([
                Action::make('create')
                    ->label('Create restaurant table')
                    ->icon('heroicon-o-plus')
                    ->url(fn (): string => RestaurantTableResource::getUrl('create'))
                    ->visible(fn (): bool => RestaurantTableResource::canCreate()),
                Action::make('resetFilters')
                    ->label('Reset filters')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->action(function ($livewire): void {
                        $livewire->resetTableFiltersForm();
                    }),
            ])
            ->columns([
                ImageColumn::make('image')
                    ->disk('public')
                    ->square()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('table_number')
                    ->label('Table')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('restaurant.name')
                    ->label('Restaurant')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('capacity')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('reservation_fee')
                    ->money('GHS')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('location')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'available' => 'success',
                        'reserved' => 'warning',
                        'occupied' => 'danger',
                        'cleaning' => 'info',
                        'maintenance' => 'gray',
                        default => 'secondary',
                    }),
            ])
            ->filters([
                SelectFilter::make('restaurant')
                    ->relationship('restaurant', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->options([
                        'available' => 'Available',
                        'reserved' => 'Reserved',
                        'occupied' => 'Occupied',
                        'cleaning' => 'Cleaning',
                        'maintenance' => 'Maintenance',
                    ]),
                Filter::make('capacity')
                    ->schema([
                        TextInput::make('minimum')
                            ->label('Minimum seats')
                            ->numeric()
                            ->minValue(1),
                        TextInput::make('maximum')
                            ->label('Maximum seats')
                            ->numeric()
                            ->minValue(1),
                    ])
                    ->columns(2)
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['minimum'] ?? null,
                            fn (Builder $query, int|string $capacity): Builder => $query->where('capacity', '>=', $capacity),
                        )
                        ->when(
                            $data['maximum'] ?? null,
                            fn (Builder $query, int|string $capacity): Builder => $query->where('capacity', '<=', $capacity),
                        )),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('print_qr')
                        ->label('Print QR Code')
                        ->icon('heroicon-o-qr-code')
                        ->color('success')
                        ->visible(fn (RestaurantTable $record): bool => filled($record->qr_token))
                        ->url(fn (RestaurantTable $record): string => route('restaurant.tables.qr.print', $record))
                        ->openUrlInNewTab(),
                    Action::make('regenerate_qr_token')
                        ->label('Regenerate QR Code')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Regenerate Table QR Code')
                        ->modalDescription('The existing printed QR code will stop working. You must print and replace it.')
                        ->action(function (RestaurantTable $record): void {
                            $record->update(['qr_token' => Str::random(48)]);
                            Notification::make()->title('QR code regenerated')
                                ->body("Print a new QR code for table {$record->table_number}.")->warning()->send();
                        }),
                ])->label('Actions')->button(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
