<?php

namespace App\Filament\Forms\Components;

use App\Filament\Forms\StateCasts\StrictDateStateCast;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\StateCasts\Contracts\StateCast;

/**
 * Uses a strict date state cast so malformed Livewire input reaches validation safely.
 */
class StrictDatePicker extends DatePicker
{
    /**
     * @return array<StateCast>
     */
    public function getDefaultStateCasts(): array
    {
        return [
            app(StrictDateStateCast::class, [
                'format' => $this->getFormat(),
                'internalFormat' => $this->getInternalFormat(),
                'timezone' => $this->getTimezone(),
            ]),
        ];
    }
}
