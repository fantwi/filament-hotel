<?php
namespace App\Filament\Admin\Resources\Promotions\Pages;
use App\Filament\Admin\Resources\Promotions\PromotionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
/**
 * Configures Filament administration for list promotions.
 */
class ListPromotions extends ListRecords { protected static string $resource = PromotionResource::class; protected function getHeaderActions(): array { return [CreateAction::make()]; } }
