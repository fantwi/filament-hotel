<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (in_array($data['department'] ?? null, ['super_admin', 'admin'], true)
            && ! auth()->user()?->hasRole('super_admin')) {
            abort(403);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if (! empty($this->data['roles'])) {
            $this->record->syncRoles([$this->data['roles']]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
