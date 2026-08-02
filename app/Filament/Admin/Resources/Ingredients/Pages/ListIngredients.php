<?php

namespace App\Filament\Admin\Resources\Ingredients\Pages;

use App\Filament\Admin\Resources\Ingredients\IngredientResource;
use App\Models\Ingredient;
use App\Services\KitchenStockService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListIngredients extends ListRecords
{
    protected static string $resource = IngredientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Add Ingredient'),
            Action::make('receiveStock')
                ->label('Receive Stock')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->form([
                    Select::make('ingredient_id')
                        ->label('Ingredient')
                        ->options(fn (): array => Ingredient::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->required(),
                    TextInput::make('quantity')
                        ->label('Quantity Received')
                        ->numeric()
                        ->minValue(.001)
                        ->step(.001)
                        ->required(),
                    TextInput::make('unit_cost')
                        ->label('Unit Cost')
                        ->numeric()
                        ->prefix('GHS')
                        ->minValue(0)
                        ->step(.01),
                    TextInput::make('reference_number')
                        ->label('Supplier / Delivery Reference')
                        ->maxLength(255),
                    TextInput::make('notes')
                        ->maxLength(1000),
                ])
                ->action(function (array $data): void {
                    $ingredient = Ingredient::findOrFail($data['ingredient_id']);

                    app(KitchenStockService::class)->receive(
                        $ingredient,
                        (float) $data['quantity'],
                        isset($data['unit_cost']) && $data['unit_cost'] !== '' ? (float) $data['unit_cost'] : null,
                        $data['reference_number'] ?? null,
                        $data['notes'] ?? null,
                    );

                    Notification::make()
                        ->title('Stock received')
                        ->body("{$ingredient->name} has been added to kitchen stock.")
                        ->success()
                        ->send();
                }),
        ];
    }
}
