<?php

namespace App\Filament\Admin\Resources\CorporateOrganizations;

use App\Filament\Admin\Resources\CorporateOrganizations\Pages\CreateCorporateOrganization;
use App\Filament\Admin\Resources\CorporateOrganizations\Pages\EditCorporateOrganization;
use App\Filament\Admin\Resources\CorporateOrganizations\Pages\ListCorporateOrganizations;
use App\Models\CorporateOrganization;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class CorporateOrganizationResource extends Resource
{
    protected static ?string $model = CorporateOrganization::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';
    protected static string|\UnitEnum|null $navigationGroup = 'System';
    protected static ?string $navigationLabel = 'Corporate Organisations';
    protected static ?int $navigationSort = 20;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager']) ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            \Filament\Forms\Components\TextInput::make('name')->required()->maxLength(255),
            \Filament\Forms\Components\TextInput::make('contact_name')->maxLength(255),
            \Filament\Forms\Components\TextInput::make('email')->email()->maxLength(255),
            \Filament\Forms\Components\TextInput::make('phone')->tel()->maxLength(50),
            \Filament\Forms\Components\TextInput::make('credit_limit')->numeric()->prefix('GHS')->minValue(0),
            \Filament\Forms\Components\TextInput::make('payment_terms_days')->numeric()->integer()->minValue(0)->default(30)->required(),
            \Filament\Forms\Components\Toggle::make('is_credit_enabled')->label('Allow deferred payment')->helperText('Linked guests can confirm bookings and food orders on this organisations account.')->default(true)->required(),
        ])->columns(['default' => 1, 'sm' => 2]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            \Filament\Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
            \Filament\Tables\Columns\TextColumn::make('contact_name')->label('Contact')->searchable()->toggleable(),
            \Filament\Tables\Columns\TextColumn::make('email')->searchable()->toggleable(),
            \Filament\Tables\Columns\TextColumn::make('credit_limit')->money('GHS')->label('Credit limit')->sortable(),
            \Filament\Tables\Columns\TextColumn::make('payment_terms_days')->label('Terms')->suffix(' days')->sortable(),
            \Filament\Tables\Columns\IconColumn::make('is_credit_enabled')->label('Deferred payment')->boolean(),
        ])->defaultSort('name')->recordActions([
            \Filament\Actions\EditAction::make(),
            \Filament\Actions\DeleteAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCorporateOrganizations::route('/'),
            'create' => CreateCorporateOrganization::route('/create'),
            'edit' => EditCorporateOrganization::route('/{record}/edit'),
        ];
    }
}
