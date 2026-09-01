<?php

namespace Tests\Feature;

use App\Filament\Admin\Concerns\InteractsWithReportPeriod;
use App\Filament\Admin\Pages\GuestReport;
use App\Filament\Admin\Pages\KitchenProductionReport;
use App\Filament\Admin\Pages\OccupancyReport;
use App\Filament\Admin\Pages\RestaurantOrderReport;
use App\Filament\Admin\Pages\RevenueReport;
use Livewire\Component;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReportPeriodControlsTest extends TestCase
{
    /**
     * @param  class-string  $pageClass
     */
    #[DataProvider('reportPages')]
    public function test_report_pages_use_shared_applied_period_controls(string $pageClass): void
    {
        self::assertContains(InteractsWithReportPeriod::class, class_uses_recursive($pageClass));

        $page = new $pageClass;

        self::assertSame('Monthly', $page->periodLabel());
    }

    public function test_report_period_edits_are_deferred_until_apply_and_reset(): void
    {
        $this->travelTo('2026-09-18 14:30:00');

        Livewire::test(ReportPeriodHarness::class)
            ->assertSet('period', 'monthly')
            ->set('draftPeriod', 'quarterly')
            ->assertSet('period', 'monthly')
            ->call('applyReportPeriod')
            ->assertHasNoErrors()
            ->assertSet('period', 'quarterly')
            ->assertSet('startDate', '2026-07-01')
            ->assertSet('endDate', '2026-09-18')
            ->call('resetReportPeriod')
            ->assertSet('period', 'monthly')
            ->assertSet('startDate', '2026-09-01')
            ->assertSet('endDate', '2026-09-18');
    }

    public function test_custom_report_period_rejects_reversed_dates(): void
    {
        Livewire::test(ReportPeriodHarness::class)
            ->set('draftPeriod', 'custom')
            ->set('draftStartDate', '2026-09-20')
            ->set('draftEndDate', '2026-09-10')
            ->call('applyReportPeriod')
            ->assertHasErrors([
                'draftStartDate' => 'before_or_equal',
                'draftEndDate' => 'after_or_equal',
            ]);
    }

    #[DataProvider('reportViews')]
    public function test_report_views_render_the_shared_period_control(string $view): void
    {
        $source = file_get_contents(resource_path("views/filament/admin/pages/{$view}.blade.php"));

        self::assertStringContainsString('<x-filament.report-period-controls', $source);
    }

    public static function reportPages(): array
    {
        return [
            GuestReport::class => [GuestReport::class],
            RevenueReport::class => [RevenueReport::class],
            OccupancyReport::class => [OccupancyReport::class],
            RestaurantOrderReport::class => [RestaurantOrderReport::class],
            KitchenProductionReport::class => [KitchenProductionReport::class],
        ];
    }

    public static function reportViews(): array
    {
        return [
            'guest report' => ['guest-report'],
            'revenue report' => ['revenue-report'],
            'occupancy report' => ['occupancy-report'],
            'restaurant order report' => ['restaurant-order-report'],
            'kitchen production report' => ['kitchen-production-report'],
        ];
    }
}

class ReportPeriodHarness extends Component
{
    use InteractsWithReportPeriod;

    public function render(): string
    {
        return '<div></div>';
    }
}
