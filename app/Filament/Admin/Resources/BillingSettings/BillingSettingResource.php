<?php

namespace App\Filament\Admin\Resources\BillingSettings;

use App\Filament\Admin\Resources\BillingSettings\Pages\CreateBillingSetting;
use App\Filament\Admin\Resources\BillingSettings\Pages\EditBillingSetting;
use App\Filament\Admin\Resources\BillingSettings\Pages\ListBillingSettings;
use App\Filament\Admin\Resources\SecureResource;
use App\Models\BillingSetting;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Configures Filament administration for billing setting resource.
 */
class BillingSettingResource extends SecureResource
{
    protected static ?string $model = BillingSetting::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calculator';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Billing Settings';

    /**
     * Determines whether the current user may view these records.
     */
    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'accountant']) ?? false;
    }

    /**
     * Configures may manage for the Filament administration interface.
     */
    private static function mayManage(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager']) ?? false;
    }

    /**
     * Determines whether the current user may create records.
     */
    public static function canCreate(): bool
    {
        return static::mayManage();
    }

    /**
     * Determines whether the current user may edit the supplied record.
     */
    public static function canEdit($record): bool
    {
        return static::mayManage();
    }

    /**
     * Determines whether the current user may delete the supplied record.
     */
    public static function canDelete($record): bool
    {
        return static::mayManage();
    }

    /**
     * Determines whether the current user may delete these records.
     */
    public static function canDeleteAny(): bool
    {
        return static::mayManage();
    }

    /**
     * Configures the form schema and input behavior.
     */
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('vat_rate')->label('VAT (%)')->numeric()->minValue(0)->maxValue(100)->required(),
            TextInput::make('nhil_rate')->label('NHIL (%)')->numeric()->minValue(0)->maxValue(100)->required(),
            TextInput::make('service_charge_rate')->label('Service Charge (%)')->numeric()->minValue(0)->maxValue(100)->required(),
        ])->columns(3);
    }

    /**
     * Configures the table data source, columns, and actions.
     */
    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('vat_rate')->suffix('%'),
            TextColumn::make('nhil_rate')->suffix('%'),
            TextColumn::make('service_charge_rate')->suffix('%'),
        ])->recordActions([EditAction::make()]);
    }

    /**
     * Builds and returns pages.
     */
    public static function getPages(): array
    {
        return [
            'index' => ListBillingSettings::route('/'),
            'create' => CreateBillingSetting::route('/create'),
            'edit' => EditBillingSetting::route('/{record}/edit'),
        ];
    }
}
