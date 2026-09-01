<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Payments\Pages\ListPayments;
use App\Filament\Admin\Widgets\PaymentReportStats;
use App\Models\User;
use App\Services\PaymentReportFilters;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Schema;
use Filament\Widgets\StatsOverviewWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaymentReportStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_report_stats_widget_supports_type_and_period_filters(): void
    {
        self::assertTrue(is_subclass_of(PaymentReportStats::class, StatsOverviewWidget::class));

        $widget = new PaymentReportStats;
        $widget->pageFilters = [
            'transaction_type' => 'food_orders',
            'period' => 'daily',
        ];
        $method = new \ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        $stats = $method->invoke($widget);

        self::assertCount(4, $stats);
    }

    public function test_payment_report_filter_options_include_all_supported_scopes(): void
    {
        self::assertSame(
            ['all', 'food_orders', 'conference_bookings', 'hotel_bookings', 'table_reservations'],
            array_keys(PaymentReportFilters::typeOptions()),
        );
        self::assertSame(
            ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'],
            array_keys(PaymentReportFilters::periodOptions()),
        );
        self::assertSame('Yearly', PaymentReportFilters::periodOptions()['yearly']);
    }

    public function test_reversed_payment_date_ranges_fall_back_instead_of_being_silently_swapped(): void
    {
        $this->travelTo('2026-08-20 12:00:00');

        [$start, $end] = PaymentReportFilters::dateRange([
            'period' => 'monthly',
            'start_date' => '2026-07-15',
            'end_date' => '2026-06-02',
        ]);

        self::assertSame('2026-08-01', $start->toDateString());
        self::assertSame('2026-08-20', $end->toDateString());
        self::assertTrue($start->lessThanOrEqualTo($end));
    }

    public function test_payments_page_registers_the_report_widget_above_the_table(): void
    {
        $page = new ListPayments;
        $method = new \ReflectionMethod($page, 'getHeaderWidgets');
        $method->setAccessible(true);

        self::assertSame([PaymentReportStats::class], $method->invoke($page));
        self::assertTrue(is_subclass_of(PaymentReportStats::class, StatsOverviewWidget::class));
    }

    public function test_payment_filters_section_spans_the_full_page_width(): void
    {
        $schema = (new ListPayments)->filtersForm(Schema::make());
        $section = $schema->getComponents()[0];

        self::assertSame(['default' => 'full'], $section->getColumnSpan());
    }

    public function test_payment_filter_form_defers_changes_and_exposes_apply_and_reset_actions(): void
    {
        $section = (new ListPayments)->filtersForm(Schema::make())->getComponents()[0];
        $components = $section->getDefaultChildComponents();

        self::assertSame([], $components[0]->getStateBindingModifiers());
        self::assertSame([], $components[1]->getStateBindingModifiers());
        self::assertSame([], $components[2]->getStateBindingModifiers());
        self::assertSame([], $components[3]->getStateBindingModifiers());

        $actions = collect($components)->first(
            fn ($component): bool => $component instanceof Actions,
        );

        self::assertInstanceOf(Actions::class, $actions);
        self::assertSame(['applyPaymentFilters', 'resetPaymentFilters'], array_map(
            fn (Action $action): string => $action->getName(),
            $actions->getDefaultChildComponents(),
        ));
    }

    public function test_payment_filters_commit_only_after_apply_and_reset_to_monthly_defaults(): void
    {
        $component = $this->paymentPage()
            ->assertSet('filters.transaction_type', 'all')
            ->assertSet('filters.period', 'monthly')
            ->set('draftFilters.transaction_type', 'food_orders')
            ->set('draftFilters.period', 'daily')
            ->assertSet('filters.transaction_type', 'all')
            ->call('applyPaymentFilters')
            ->assertHasNoErrors()
            ->assertSet('filters.transaction_type', 'food_orders')
            ->assertSet('filters.period', 'daily');

        $component
            ->call('resetPaymentFilters')
            ->assertSet('filters.transaction_type', 'all')
            ->assertSet('filters.period', 'monthly');
    }

    public function test_payment_filter_apply_rejects_a_reversed_custom_range(): void
    {
        $this->paymentPage()
            ->set('draftFilters.start_date', '2026-08-20')
            ->set('draftFilters.end_date', '2026-08-01')
            ->call('applyPaymentFilters')
            ->assertHasErrors([
                'draftFilters.start_date' => 'before_or_equal',
                'draftFilters.end_date' => 'after_or_equal',
            ]);
    }

    private function paymentPage(): Testable
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create(['department' => 'admin']);
        $admin->assignRole('admin');

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        return Livewire::actingAs($admin)->test(ListPayments::class);
    }
}
