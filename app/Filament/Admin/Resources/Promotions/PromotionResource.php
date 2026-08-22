<?php

namespace App\Filament\Admin\Resources\Promotions;

use App\Filament\Admin\Resources\Promotions\Pages\CreatePromotion;
use App\Filament\Admin\Resources\Promotions\Pages\EditPromotion;
use App\Filament\Admin\Resources\Promotions\Pages\ListPromotions;
use App\Models\Promotion;
use BackedEnum;
use App\Filament\Admin\Resources\SecureResource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

/**
 * Configures Filament administration for promotion resource.
 */
class PromotionResource extends SecureResource
{
    protected static ?string $model = Promotion::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';
    protected static string|\UnitEnum|null $navigationGroup = 'System';
    protected static ?string $navigationLabel = 'Promotions';

    /**
     * Determines whether the current user may view these records.
     */
    public static function canViewAny(): bool { return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager']) ?? false; }

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
            \Filament\Forms\Components\TextInput::make('name')->required(),
            \Filament\Forms\Components\TextInput::make('code')->required()->dehydrateStateUsing(fn (string $state): string => strtoupper($state))->unique(ignoreRecord: true),
            \Filament\Forms\Components\Select::make('discount_type')->options(['percentage' => 'Percentage', 'fixed' => 'Fixed amount'])->required(),
            \Filament\Forms\Components\TextInput::make('discount_value')->numeric()->minValue(0)->required(),
            \Filament\Forms\Components\TextInput::make('minimum_spend')->numeric()->minValue(0)->prefix('GHS'),
            \Filament\Forms\Components\DatePicker::make('starts_at'),
            \Filament\Forms\Components\DatePicker::make('ends_at')->afterOrEqual('starts_at'),
            \Filament\Forms\Components\Toggle::make('is_active')->default(true),
        ])->columns(2);
    }

    /**
     * Configures the table data source, columns, and actions.
     */
    public static function table(Table $table): Table
    {
        return $table->columns([
            \Filament\Tables\Columns\TextColumn::make('name')->searchable(),
            \Filament\Tables\Columns\TextColumn::make('code')->searchable()->copyable(),
            \Filament\Tables\Columns\TextColumn::make('discount_type')->badge(),
            \Filament\Tables\Columns\TextColumn::make('discount_value')->numeric(),
            \Filament\Tables\Columns\IconColumn::make('is_active')->boolean(),
        ])->recordActions([\Filament\Actions\EditAction::make(), \Filament\Actions\DeleteAction::make()]);
    }

    /**
     * Builds and returns pages.
     */
    public static function getPages(): array { return ['index' => ListPromotions::route('/'), 'create' => CreatePromotion::route('/create'), 'edit' => EditPromotion::route('/{record}/edit')]; }
}
