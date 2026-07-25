<?php
namespace App\Filament\Admin\Resources\KitchenProductions\Pages;
use App\Filament\Admin\Resources\KitchenProductions\KitchenProductionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
class ListKitchenProductions extends ListRecords { protected static string $resource = KitchenProductionResource::class; protected function getHeaderActions(): array { return [CreateAction::make()]; } }
