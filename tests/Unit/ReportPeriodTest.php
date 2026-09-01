<?php

namespace Tests\Unit;

use App\Support\Reporting\ReportPeriod;
use InvalidArgumentException;
use Tests\TestCase;

class ReportPeriodTest extends TestCase
{
    public function test_report_period_options_and_ranges_use_one_vocabulary(): void
    {
        $this->travelTo('2026-09-18 14:30:00');

        self::assertSame(
            ['daily', 'weekly', 'monthly', 'quarterly', 'yearly', 'custom'],
            array_keys(ReportPeriod::options()),
        );

        [$start, $end] = ReportPeriod::range('quarterly');

        self::assertTrue($start->isStartOfQuarter());
        self::assertSame(now()->toDateString(), $end->toDateString());
        self::assertSame('Quarterly', ReportPeriod::label('quarterly'));
    }

    public function test_custom_report_period_uses_inclusive_day_boundaries(): void
    {
        [$start, $end] = ReportPeriod::range('custom', '2026-09-10', '2026-09-12');

        self::assertSame('2026-09-10 00:00:00', $start->format('Y-m-d H:i:s'));
        self::assertSame('2026-09-12 23:59:59', $end->format('Y-m-d H:i:s'));
        self::assertSame(
            'Sep 10, 2026 to Sep 12, 2026',
            ReportPeriod::label('custom', '2026-09-10', '2026-09-12'),
        );
    }

    public function test_custom_report_period_rejects_a_reversed_range(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ReportPeriod::range('custom', '2026-09-20', '2026-09-10');
    }

    public function test_unknown_report_period_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ReportPeriod::range('all');
    }
}
