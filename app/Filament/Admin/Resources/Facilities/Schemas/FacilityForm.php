<?php

namespace App\Filament\Admin\Resources\Facilities\Schemas;

use App\Models\Facility;
use Closure;
use Filament\Forms;
use Filament\Schemas\Schema;

/**
 * Configures Filament administration for facility form.
 */
class FacilityForm
{
    /**
     * Configures configure for the Filament administration interface.
     */
    public static function configure(Schema $schema): Schema
    {
        // return $schema
        //     ->components([
        //         //
        //     ]);

        return $schema
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->trim()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->rule(fn (?Facility $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                        if (Facility::hasEquivalentName((string) $value, $record?->getKey())) {
                            $fail('The facility name has already been taken.');
                        }
                    }),
                Forms\Components\TextInput::make('icon')
                    ->helperText(
                        'Optional icon name'
                    ),
                Forms\Components\Toggle::make('is_published')
                    ->label('Published for guests')
                    ->helperText('Only you can see this facility in Filament until it is published.')
                    ->onIcon('heroicon-m-eye')
                    ->offIcon('heroicon-m-eye-slash')
                    ->default(false),
            ]);
    }
}
