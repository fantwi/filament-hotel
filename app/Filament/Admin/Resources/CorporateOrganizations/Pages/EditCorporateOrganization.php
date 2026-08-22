<?php

namespace App\Filament\Admin\Resources\CorporateOrganizations\Pages;

use App\Filament\Admin\Resources\CorporateOrganizations\CorporateOrganizationResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

/**
 * Configures Filament administration for edit corporate organization.
 */
class EditCorporateOrganization extends EditRecord
{
    protected static string $resource = CorporateOrganizationResource::class;

    /**
     * Builds and returns header actions.
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('link_guest')
                ->label('Link Guest')
                ->icon('heroicon-o-user-plus')
                ->form([
                    Select::make('user_id')
                        ->label('Guest')
                        ->options(fn (): array => User::query()
                            ->where('department', 'guest')
                            ->where(function ($query): void {
                                $query->whereNull('corporate_organization_id')
                                    ->orWhere('corporate_organization_id', $this->record->id);
                            })
                            ->orderBy('first_name')
                            ->orderBy('last_name')
                            ->get()
                            ->mapWithKeys(fn (User $user): array => [
                                $user->id => $user->name.' ('.$user->email.')',
                            ])
                            ->all())
                        ->searchable()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $guest = User::query()
                        ->whereKey($data['user_id'])
                        ->where('department', 'guest')
                        ->firstOrFail();

                    $guest->update([
                        'corporate_organization_id' => $this->record->id,
                    ]);

                    Notification::make()
                        ->title('Guest linked to corporate account')
                        ->body($guest->name.' can now use '.$this->record->name.' for deferred payment.')
                        ->success()
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }
}
