<?php

namespace App\Filament\Admin\Resources\Guests;

use App\Filament\Admin\Resources\Guests\Pages\CreateGuest;
use App\Filament\Admin\Resources\Guests\Pages\EditGuest;
use App\Filament\Admin\Resources\Guests\Pages\ListGuests;
use App\Filament\Admin\Resources\Guests\Pages\ViewGuest;
use App\Filament\Admin\Resources\Guests\Schemas\GuestForm;
use App\Filament\Admin\Resources\Guests\Schemas\GuestInfolist;
use App\Filament\Admin\Resources\Guests\Tables\GuestsTable;
use App\Models\Guest;
use BackedEnum;
use App\Filament\Admin\Resources\SecureResource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Configures Filament administration for guest resource.
 */
class GuestResource extends SecureResource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Guest Management';

    protected static ?int $navigationSort = 10;

    protected static ?string $model = Guest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'first_name';

    /**
     * Controls whether this feature appears in the Filament navigation.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole([
            'super_admin',
            'admin',
            'receptionist',
        ]);
    }

    // Check if user can view guests
    /**
     * Determines whether the current user may view these records.
     */
    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole([
            'super_admin',
            'admin',
            'receptionist',
        ]);
    }

    /**
     * Determines whether the current user may create records.
     */
    public static function canCreate(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'receptionist']) ?? false;
    }

    /**
     * Determines whether the current user may edit the supplied record.
     */
    public static function canEdit($record): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'receptionist']) ?? false;
    }

    /**
     * Determines whether the current user may delete the supplied record.
     */
    public static function canDelete($record): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    /**
     * Determines whether the current user may delete these records.
     */
    public static function canDeleteAny(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }


    /**
     * Configures the form schema and input behavior.
     */
    public static function form(Schema $schema): Schema
    {
        return GuestForm::configure($schema);
    }

    /**
     * Configures the read-only record details shown in the admin panel.
     */
    public static function infolist(Schema $schema): Schema
    {
        return GuestInfolist::configure($schema);
    }

    /**
     * Configures the table data source, columns, and actions.
     */
    public static function table(Table $table): Table
    {
        return GuestsTable::configure($table);
    }

    /**
     * Builds and returns relations.
     */
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * Builds and returns pages.
     */
    public static function getPages(): array
    {
        return [
            'index' => ListGuests::route('/'),
            'create' => CreateGuest::route('/create'),
            'view' => ViewGuest::route('/{record}'),
            'edit' => EditGuest::route('/{record}/edit'),
        ];
    }

    /**
     * Configures after create for the Filament administration interface.
     */
    protected function afterCreate(): void
    {
        $this->record->assignRole('guest');
    }
}
