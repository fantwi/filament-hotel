<?php

namespace Tests\Unit;

use App\Filament\Forms\StateCasts\StrictDateStateCast;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StrictDateStateCastTest extends TestCase
{
    #[DataProvider('validStringStates')]
    public function test_supported_string_states_keep_their_instant_and_calendar_date(
        string $state,
        string $expectedDate,
        string $expectedInternalState,
    ): void {
        config(['app.timezone' => 'Africa/Accra']);

        $cast = $this->cast();

        self::assertSame($expectedDate, $cast->get($state));
        self::assertSame($expectedInternalState, $cast->set($state));
    }

    public static function validStringStates(): array
    {
        return [
            'Laravel ISO serialization' => [
                '2026-09-10T00:00:00.000000Z',
                '2026-09-10',
                '2026-09-10 00:00:00',
            ],
            'positive ISO offset with fractional seconds' => [
                '2026-09-10T12:15:30.123+05:30',
                '2026-09-10',
                '2026-09-10 06:45:30',
            ],
            'negative ISO offset' => [
                '2026-09-10T01:15:30-04:00',
                '2026-09-10',
                '2026-09-10 05:15:30',
            ],
            'database state' => [
                '2026-09-10 08:30:00',
                '2026-09-10',
                '2026-09-10 08:30:00',
            ],
            'browser state' => [
                '2026-09-10',
                '2026-09-10',
                '2026-09-10 00:00:00',
            ],
        ];
    }

    public function test_iso_offsets_use_the_application_timezone_for_the_calendar_date(): void
    {
        config(['app.timezone' => 'America/New_York']);

        $cast = $this->cast('UTC');

        self::assertSame('2026-01-01', $cast->get('2026-01-02T00:30:00+02:00'));
        self::assertSame('2026-01-01 22:30:00', $cast->set('2026-01-02T00:30:00+02:00'));
    }

    public function test_carbon_interface_and_null_states_remain_supported(): void
    {
        config(['app.timezone' => 'Africa/Accra']);

        $cast = $this->cast();

        foreach ([
            Carbon::create(2026, 9, 10, 8, 30, 0, 'UTC'),
            CarbonImmutable::create(2026, 9, 10, 8, 30, 0, 'UTC'),
        ] as $state) {
            self::assertSame('2026-09-10', $cast->get($state));
            self::assertSame('2026-09-10 08:30:00', $cast->set($state));
        }

        self::assertNull($cast->get(null));
        self::assertNull($cast->set(null));
    }

    #[DataProvider('invalidStringStates')]
    public function test_malformed_and_impossible_string_states_are_rejected(string $state): void
    {
        $cast = $this->cast();

        self::assertNull($cast->get($state));
        self::assertNull($cast->set($state));
    }

    public static function invalidStringStates(): array
    {
        return [
            'arbitrary text' => ['not-a-date'],
            'impossible browser date' => ['2026-02-30'],
            'impossible database date' => ['2026-02-30 00:00:00'],
            'impossible ISO date with Z offset' => ['2026-02-30T00:00:00Z'],
            'impossible ISO date with fractional seconds and positive offset' => ['2026-04-31T12:30:00.123456+05:30'],
            'impossible ISO hour with negative offset' => ['2026-01-01T24:00:00-07:00'],
            'impossible ISO minute' => ['2026-01-01T23:60:00Z'],
        ];
    }

    private function cast(string $timezone = 'Africa/Accra'): StrictDateStateCast
    {
        return new StrictDateStateCast('Y-m-d', 'Y-m-d H:i:s', $timezone);
    }
}
