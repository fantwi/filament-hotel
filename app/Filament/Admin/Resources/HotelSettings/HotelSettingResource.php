<?php

namespace App\Filament\Admin\Resources\HotelSettings;

use App\Filament\Admin\Resources\HotelSettings\Pages\CreateHotelSetting;
use App\Filament\Admin\Resources\HotelSettings\Pages\EditHotelSetting;
use App\Filament\Admin\Resources\HotelSettings\Pages\ListHotelSettings;
use App\Filament\Admin\Resources\SecureResource;
use App\Models\HotelSetting;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Configures Filament administration for hotel setting resource.
 */
class HotelSettingResource extends SecureResource
{
    protected static ?string $model = HotelSetting::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|\UnitEnum|null $navigationGroup = 'Hotel Configuration';

    protected static ?string $navigationLabel = 'Hotel Branding';

    protected static ?int $navigationSort = 1;

    /**
     * Determines whether the current user may view these records.
     */
    public static function canViewAny(): bool
    {
        return static::mayManage();
    }

    /**
     * Configures may manage for the Filament administration interface.
     */
    private static function mayManage(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    /**
     * Determines whether the current user may create records.
     */
    public static function canCreate(): bool
    {
        return static::mayManage() && ! HotelSetting::query()->exists();
    }

    /**
     * Determines whether the current user may edit the supplied record.
     */
    public static function canEdit($record): bool
    {
        return static::mayManage();
    }

    /**
     * Configures the form schema and input behavior.
     */
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Hotel identity')
                ->description('This name and logo are shown in the guest navigation and footer across the website.')
                ->schema([
                    TextInput::make('hotel_name')
                        ->label('Hotel name')
                        ->required()
                        ->maxLength(255),

                    FileUpload::make('logo')
                        ->label('Hotel logo')
                        ->image()
                        ->disk('public')
                        ->directory('hotel-branding')
                        ->visibility('public')
                        ->acceptedFileTypes([
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ])
                        ->maxSize(5120)
                        ->rules(['dimensions:max_width=4096,max_height=4096'])
                        ->helperText('Upload a JPG, PNG, or WebP logo up to 5 MB. A square image works best.')
                        ->columnSpanFull(),
                ])
                ->columns(['default' => 1, 'sm' => 2]),

            Section::make('Color scheme')
                ->description('These colors update shared navigation, links, call-to-action buttons, and the footer across guest pages.')
                ->schema([
                    ColorPicker::make('primary_color')
                        ->label('Primary color')
                        ->required()
                        ->default('#2563EB'),

                    ColorPicker::make('secondary_color')
                        ->label('Accent color')
                        ->required()
                        ->default('#0EA5E9'),

                    ColorPicker::make('footer_color')
                        ->label('Footer and dark-mode color')
                        ->required()
                        ->default('#161B48'),
                ])
                ->columns(['default' => 1, 'sm' => 3]),
        ]);
    }

    /**
     * Configures the table data source, columns, and actions.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo')
                    ->label('Logo')
                    ->disk('public')
                    ->circular(),
                TextColumn::make('hotel_name')
                    ->label('Hotel name')
                    ->searchable(),
                TextColumn::make('updated_at')
                    ->label('Last updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    /**
     * Builds and returns pages.
     */
    public static function getPages(): array
    {
        return [
            'index' => ListHotelSettings::route('/'),
            'create' => CreateHotelSetting::route('/create'),
            'edit' => EditHotelSetting::route('/{record}/edit'),
        ];
    }
}
