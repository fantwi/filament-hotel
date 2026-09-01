<?php

namespace Tests\Feature;

use Tests\TestCase;

class FilamentCustomTableAccessibilityTest extends TestCase
{
    /**
     * @var list<string>
     */
    private const MAINTAINED_TABLE_FILES = [
        'views/filament/admin/widgets/corporate-billing-overview.blade.php',
        'views/filament/admin/pages/corporate-receivables.blade.php',
        'views/filament/admin/pages/guest-report.blade.php',
        'views/filament/admin/pages/kitchen-production-report.blade.php',
        'views/filament/admin/pages/restaurant-order-report.blade.php',
    ];

    public function test_maintained_custom_admin_tables_have_captions_and_scoped_headers(): void
    {
        $tableCount = 0;
        $headerCount = 0;
        $violations = [];

        foreach (self::MAINTAINED_TABLE_FILES as $relativePath) {
            $contents = file_get_contents(resource_path($relativePath));

            self::assertNotFalse($contents, "Unable to read maintained view [{$relativePath}].");
            preg_match_all('/<table\b[^>]*>(.*?)<\/table>/is', $contents, $tables);

            foreach ($tables[1] as $tableIndex => $table) {
                $tableCount++;
                $tableLabel = "{$relativePath} table ".($tableIndex + 1);

                if (! preg_match('/<caption\b[^>]*>\s*(.*?)\s*<\/caption>/is', $table, $caption)) {
                    $violations[] = "{$tableLabel} is missing a caption.";
                } elseif (trim(strip_tags($caption[1])) === '') {
                    $violations[] = "{$tableLabel} has an empty caption.";
                }

                preg_match('/<thead\b[^>]*>(.*?)<\/thead>/is', $table, $thead);
                preg_match_all('/<th\b[^>]*>/i', $thead[1] ?? '', $columnHeaders);
                $headerCount += count($columnHeaders[0]);

                preg_match_all('/<th\b[^>]*>/i', $table, $headers);

                foreach ($headers[0] as $headerIndex => $header) {
                    if (! preg_match('/\bscope\s*=\s*["\'](?:col|row)["\']/i', $header)) {
                        $violations[] = "{$tableLabel} header ".($headerIndex + 1).' is missing scope="col" or scope="row".';
                    }
                }
            }
        }

        self::assertSame(5, $tableCount);
        self::assertSame(32, $headerCount);
        self::assertSame([], $violations, implode(PHP_EOL, $violations));
    }
}
