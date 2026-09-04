<?php

namespace Tests\Feature;

use Tests\TestCase;

class FilamentCustomTableAccessibilityTest extends TestCase
{
    /**
     * @var array<string, array{columnHeaders: int, rowHeaders: int}>
     */
    private const MAINTAINED_TABLES = [
        'views/filament/admin/widgets/corporate-billing-overview.blade.php' => ['columnHeaders' => 6, 'rowHeaders' => 1],
        'views/filament/admin/pages/corporate-receivables.blade.php' => ['columnHeaders' => 6, 'rowHeaders' => 1],
        'views/filament/admin/pages/guest-report.blade.php' => ['columnHeaders' => 4, 'rowHeaders' => 1],
        'views/filament/admin/pages/kitchen-production-report.blade.php' => ['columnHeaders' => 12, 'rowHeaders' => 1],
        'views/filament/admin/pages/restaurant-order-report.blade.php' => ['columnHeaders' => 6, 'rowHeaders' => 1],
    ];

    public function test_maintained_custom_admin_tables_have_valid_captions_and_header_scopes(): void
    {
        $tableCount = 0;
        $columnHeaderCount = 0;
        $rowHeaderCount = 0;
        $violations = [];

        foreach (self::MAINTAINED_TABLES as $relativePath => $expected) {
            $contents = file_get_contents(resource_path($relativePath));

            self::assertNotFalse($contents, "Unable to read maintained view [{$relativePath}].");
            preg_match_all('/<table\b[^>]*>(.*?)<\/table>/is', $contents, $tables);

            foreach ($tables[1] as $tableIndex => $table) {
                $tableCount++;
                $tableLabel = "{$relativePath} table ".($tableIndex + 1);

                preg_match_all('/<caption\b[^>]*>.*?<\/caption>/is', $table, $captions);

                if (count($captions[0]) !== 1) {
                    $violations[] = "{$tableLabel} must have exactly one caption.";
                } elseif (! preg_match('/^\s*<caption\b([^>]*)>(.*?)<\/caption>/is', $table, $caption)) {
                    $violations[] = "{$tableLabel} caption must be the first direct table child.";
                } elseif (! preg_match('/\bclass\s*=\s*["\'][^"\']*\bsr-only\b[^"\']*["\']/i', $caption[1])) {
                    $violations[] = "{$tableLabel} caption must include the sr-only class.";
                } elseif (trim(strip_tags($caption[2])) === '') {
                    $violations[] = "{$tableLabel} caption must not be empty.";
                }

                if (! preg_match('/<thead\b[^>]*>(.*?)<\/thead>/is', $table, $thead)) {
                    $violations[] = "{$tableLabel} is missing a thead.";
                    $theadMarkup = '';
                } else {
                    $theadMarkup = $thead[1];
                }

                preg_match_all('/<th\b[^>]*>/i', $theadMarkup, $columnHeaders);
                $columnHeaderCount += count($columnHeaders[0]);

                if (count($columnHeaders[0]) !== $expected['columnHeaders']) {
                    $violations[] = "{$tableLabel} must have {$expected['columnHeaders']} column headers.";
                }

                foreach ($columnHeaders[0] as $headerIndex => $header) {
                    preg_match_all('/\bscope\s*=\s*([\'"])(.*?)\1/i', $header, $scopes);

                    if (count($scopes[0]) !== 1 || ($scopes[2][0] ?? '') !== 'col') {
                        $violations[] = "{$tableLabel} column header ".($headerIndex + 1).' must have exactly scope="col".';
                    }
                }

                if (! preg_match('/<tbody\b[^>]*>(.*?)<\/tbody>/is', $table, $tbody)) {
                    $violations[] = "{$tableLabel} is missing a tbody.";
                    $tbodyMarkup = '';
                } else {
                    $tbodyMarkup = $tbody[1];
                }

                preg_match_all('/<th\b[^>]*>/i', $tbodyMarkup, $rowHeaders);
                $rowHeaderCount += count($rowHeaders[0]);

                if (count($rowHeaders[0]) !== $expected['rowHeaders']) {
                    $violations[] = "{$tableLabel} must have {$expected['rowHeaders']} identifying row header.";
                }

                foreach ($rowHeaders[0] as $headerIndex => $header) {
                    preg_match_all('/\bscope\s*=\s*([\'"])(.*?)\1/i', $header, $scopes);

                    if (count($scopes[0]) !== 1 || ($scopes[2][0] ?? '') !== 'row') {
                        $violations[] = "{$tableLabel} tbody header ".($headerIndex + 1).' must have exactly scope="row".';
                    }
                }

                if (preg_match('/<td\b[^>]*\bscope\s*=/i', $tbodyMarkup)) {
                    $violations[] = "{$tableLabel} has a tbody data cell with a scope attribute.";
                }

                preg_match_all('/<tr\b[^>]*>(.*?)<\/tr>/is', $tbodyMarkup, $rows);

                foreach ($rows[1] as $rowIndex => $row) {
                    if (! preg_match('/\bscope\s*=\s*[\'"]row[\'"]/i', $row)) {
                        continue;
                    }

                    preg_match('/<(?:th|td)\b[^>]*>/i', $row, $firstCell);

                    if (! isset($firstCell[0]) || ! preg_match('/^<th\b[^>]*\bscope\s*=\s*[\'"]row[\'"]/i', $firstCell[0])) {
                        $violations[] = "{$tableLabel} data row ".($rowIndex + 1).' must put its scope="row" header in the first identifying cell.';
                    }
                }

                preg_match_all('/<th\b[^>]*>/i', $table, $allHeaders);
                $expectedHeaderCount = $expected['columnHeaders'] + $expected['rowHeaders'];

                if (count($allHeaders[0]) !== $expectedHeaderCount) {
                    $violations[] = "{$tableLabel} must contain exactly {$expectedHeaderCount} total headers.";
                }
            }
        }

        self::assertSame(5, $tableCount);
        self::assertSame(34, $columnHeaderCount);
        self::assertSame(5, $rowHeaderCount);
        self::assertSame([], $violations, implode(PHP_EOL, $violations));
    }
}
