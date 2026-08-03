<?php

namespace App\Filament\Admin\Resources\BillingSettings;

use App\Filament\Admin\Resources\BillingSettings\Pages\CreateBillingSetting;
use App\Filament\Admin\Resources\BillingSettings\Pages\EditBillingSetting;
use App\Filament\Admin\Resources\BillingSettings\Pages\ListBillingSettings;
use App\Models\BillingSetting;
use BackedEnum;
use App\Filament\Admin\Resources\SecureResource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class BillingSettingResource extends SecureResource
{
    protected static ?string $model = BillingSetting::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calculator';
    protected static string|\UnitEnum|null $navigationGroup = 'System';
    protected static ?string $navigationLabel = 'Billing Settings';

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager', 'accountant']) ?? false;
    }

    private static function mayManage(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager']) ?? false;
    }

    public static function canCreate(): bool
    {
        return static::mayManage();
    }

    public static function canEdit($record): bool
    {
        return static::mayManage();
    }

    public static function canDelete($record): bool
    {
        return static::mayManage();
    }

    public static function canDeleteAny(): bool
    {
        return static::mayManage();
    }


    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            \Filament\Forms\Components\TextInput::make('vat_rate')->label('VAT (%)')->numeric()->minValue(0)->maxValue(100)->required(),
            \Filament\Forms\Components\TextInput::make('nhil_rate')->label('NHIL (%)')->numeric()->minValue(0)->maxValue(100)->required(),
            \Filament\Forms\Components\TextInput::make('service_charge_rate')->label('Service Charge (%)')->numeric()->minValue(0)->maxValue(100)->required(),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            \Filament\Tables\Columns\TextColumn::make('vat_rate')->suffix('%'),
            \Filament\Tables\Columns\TextColumn::make('nhil_rate')->suffix('%'),
            \Filament\Tables\Columns\TextColumn::make('service_charge_rate')->suffix('%'),
        ])->recordActions([\Filament\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBillingSettings::route('/'),
            'create' => CreateBillingSetting::route('/create'),
            'edit' => EditBillingSetting::route('/{record}/edit'),
        ];
    }
}
