<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    // protected function mutateFormDataBeforeCreate(array $data): array
    // {
    //     dd($data); // DEBUG
    // }

    protected function afterCreate(): void
    {
        if (!empty($this->data['roles'])) {
            $this->record->syncRoles([$this->data['roles']]);
        }
    }
}
