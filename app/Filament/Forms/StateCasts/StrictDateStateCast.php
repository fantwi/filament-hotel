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

        if (preg_match(
            '/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.(?<fraction>\d{1,6}))?(?<offset>Z|[+-]\d{2}:\d{2})\z/',
            $state,
            $matches,
        )) {
            return $this->parseIso($state, $matches['fraction'] ?? '', $matches['offset']);
        }

        foreach ([$this->internalFormat, $this->format] as $format) {
            $date = $this->parseExact("!{$format}", $state, $this->timezone);

            if ($date?->format($format) === $state) {
                return $date;
            }
        }

        return null;
    }

    private function parseIso(string $state, string $fraction, string $offset): ?Carbon
    {
        $format = '!Y-m-d\TH:i:s'.($fraction === '' ? '' : '.u').'P';
        $date = $this->parseExact($format, $state);

        if (! $date) {
            return null;
        }

        $roundTrip = $date->format('Y-m-d\TH:i:s');

        if ($fraction !== '') {
            $roundTrip .= '.'.substr($date->format('u'), 0, strlen($fraction));
        }

        $roundTrip .= $offset === '-00:00'
            ? $offset
            : $date->format($offset === 'Z' ? 'p' : 'P');

        return $roundTrip === $state ? $date : null;
    }

    private function parseExact(string $format, string $state, ?string $timezone = null): ?Carbon
    {
        try {
            $date = Carbon::createFromFormat($format, $state, $timezone);
        } catch (\Throwable) {
            return null;
        }

        $errors = Carbon::getLastErrors();

        if ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
            return null;
        }

        return $date;
    }
}
