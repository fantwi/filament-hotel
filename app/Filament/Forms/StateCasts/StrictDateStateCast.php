<?php

namespace App\Filament\Forms\StateCasts;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Filament\Schemas\Components\StateCasts\Contracts\StateCast;

/**
 * Safely converts date-only form state without accepting malformed input.
 */
class StrictDateStateCast implements StateCast
{
    public function __construct(
        protected string $format,
        protected string $internalFormat,
        protected string $timezone,
    ) {}

    public function get(mixed $state): ?string
    {
        if (blank($state)) {
            return null;
        }

        $date = $state instanceof CarbonInterface
            ? Carbon::instance($state)
            : $this->parse($state);

        return $date?->setTimezone(config('app.timezone'))->format($this->format);
    }

    public function set(mixed $state): ?string
    {
        if (blank($state)) {
            return null;
        }

        $date = $state instanceof CarbonInterface
            ? Carbon::instance($state)
            : $this->parse($state);

        return $date?->setTimezone($this->timezone)->format($this->internalFormat);
    }

    private function parse(mixed $state): ?Carbon
    {
        if (! is_string($state)) {
            return null;
        }

        if (preg_match('/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-]\d{2}:\d{2})\z/', $state)) {
            try {
                return Carbon::parse($state);
            } catch (\Throwable) {
                return null;
            }
        }

        foreach ([$this->internalFormat, $this->format] as $format) {
            try {
                $date = Carbon::createFromFormat("!{$format}", $state, $this->timezone);
            } catch (\Throwable) {
                continue;
            }

            $errors = Carbon::getLastErrors();

            if (($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
                && $date->format($format) === $state) {
                return $date;
            }
        }

        return null;
    }
}
