<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Configures Filament administration for edit user.
 */
class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /**
     * Configures mutate form data before save for the Filament administration interface.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (in_array($data['department'] ?? null, ['super_admin', 'admin'], true)
            && ! auth()->user()?->hasRole('super_admin')) {
            abort(403);
        }

        return $data;
    }

    /**
     * Configures after save for the Filament administration interface.
     */
    protected function afterSave(): void
    {
        if (! empty($this->data['roles'])) {
            $this->record->syncRoles([$this->data['roles']]);
        }
    }

    /**
     * Builds and returns header actions.
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
