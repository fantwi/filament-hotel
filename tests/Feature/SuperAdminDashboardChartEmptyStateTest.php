<?php

namespace Tests\Feature;

use App\Filament\Admin\Widgets\RestaurantOrderStatusChart;
use App\Filament\Admin\Widgets\RestaurantRevenueChart;
use App\Models\RestaurantOrder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SuperAdminDashboardChartEmptyStateTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('restaurantCharts')]
    public function test_restaurant_chart_is_empty_when_the_selected_period_has_no_orders(string $widgetClass): void
    {
        $widget = $this->widgetForAugust($widgetClass);

        self::assertTrue($widget->isEmpty());
    }

    #[DataProvider('restaurantCharts')]
    public function test_restaurant_chart_is_not_empty_when_the_selected_period_has_an_order(string $widgetClass): void
    {
        $order = RestaurantOrder::query()->create([
            'order_number' => 'EMPTY-STATE-ORDER',
            'payment_status' => 'completed',
            'status' => 'confirmed',
            'total' => 150,
            'ordering_channel' => 'web',
        ]);
        $order->forceFill([
            'created_at' => Carbon::parse('2026-08-15 12:00:00'),
            'updated_at' => Carbon::parse('2026-08-15 12:00:00'),
        ])->saveQuietly();

        self::assertFalse($this->widgetForAugust($widgetClass)->isEmpty());
    }

    #[DataProvider('restaurantCharts')]
    public function test_restaurant_chart_empty_state_explains_how_to_find_data(string $widgetClass): void
    {
        $widget = $this->widgetForAugust($widgetClass);

        self::assertSame('No restaurant data for this period', $widget->getEmptyStateHeading());
        self::assertSame(
            'Choose another dashboard period or date range to view restaurant activity.',
            $widget->getEmptyStateDescription(),
        );
    }

    /**
     * @return array<string, array{0: class-string}>
     */
    public static function restaurantCharts(): array
    {
        return [
            'restaurant revenue chart' => [RestaurantRevenueChart::class],
            'restaurant order status chart' => [RestaurantOrderStatusChart::class],
        ];
    }

    /**
     * @param  class-string  $widgetClass
     */
    private function widgetForAugust(string $widgetClass): RestaurantRevenueChart|RestaurantOrderStatusChart
    {
        $widget = new $widgetClass;
        $widget->pageFilters = [
            'period' => 'monthly',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
        ];

        return $widget;
    }
}
