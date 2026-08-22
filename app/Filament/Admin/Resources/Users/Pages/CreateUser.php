<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Configures Filament administration for create user.
 */
class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * Configures mutate form data before create for the Filament administration interface.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->ensureDepartmentMayBeAssigned($data['department'] ?? null);

        return $data;
    }

    /**
     * Configures ensure department may be assigned for the Filament administration interface.
     */
    private function ensureDepartmentMayBeAssigned(?string $department): void
    {
        if (in_array($department, ['super_admin', 'admin'], true)
            && ! auth()->user()?->hasRole('super_admin')) {
            abort(403);
        }
    }

    /**
     * Configures after create for the Filament administration interface.
     */
    protected function afterCreate(): void
    {
        if (! empty($this->data['roles'])) {
            $this->record->syncRoles([$this->data['roles']]);
        }
    }
}
