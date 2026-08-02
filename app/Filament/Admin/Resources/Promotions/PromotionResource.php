<?php

namespace App\Filament\Admin\Resources\Promotions;

use App\Filament\Admin\Resources\Promotions\Pages\CreatePromotion;
use App\Filament\Admin\Resources\Promotions\Pages\EditPromotion;
use App\Filament\Admin\Resources\Promotions\Pages\ListPromotions;
use App\Models\Promotion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class PromotionResource extends Resource
{
    protected static ?string $model = Promotion::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';
    protected static string|\UnitEnum|null $navigationGroup = 'System';
    protected static ?string $navigationLabel = 'Promotions';

    public static function canViewAny(): bool { return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager']) ?? false; }

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

    public static function getPages(): array { return ['index' => ListPromotions::route('/'), 'create' => CreatePromotion::route('/create'), 'edit' => EditPromotion::route('/{record}/edit')]; }
}
