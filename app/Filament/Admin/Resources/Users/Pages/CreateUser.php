<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->ensureDepartmentMayBeAssigned($data['department'] ?? null);

        return $data;
    }

    private function ensureDepartmentMayBeAssigned(?string $department): void
    {
        if (in_array($department, ['super_admin', 'admin'], true)
            && ! auth()->user()?->hasRole('super_admin')) {
            abort(403);
        }
    }

    protected function afterCreate(): void
    {
        if (! empty($this->data['roles'])) {
            $this->record->syncRoles([$this->data['roles']]);
        }
    }
}
