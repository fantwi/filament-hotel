<?php

namespace Tests\Feature;

use App\Enums\StaffAccountStatus;
use App\Filament\Admin\Resources\KitchenStockMovements\Pages\ListKitchenStockMovements;
use App\Filament\Admin\Widgets\KitchenStockMovementStats;
use App\Models\Ingredient;
use App\Models\KitchenStockMovement;
use App\Models\Restaurant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use ReflectionMethod;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class KitchenStockMovementReportingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_page_explains_the_immutable_ledger_and_registers_period_aware_stats(): void
    {
        $page = new ListKitchenStockMovements;
        $widgets = new ReflectionMethod($page, 'getHeaderWidgets');
        $widgets->setAccessible(true);

        self::assertSame([KitchenStockMovementStats::class], $widgets->invoke($page));
        self::assertStringContainsString('Immutable audit ledger', (string) $page->getSubheading());
        self::assertTrue(is_subclass_of(KitchenStockMovementStats::class, StatsOverviewWidget::class));
    }

    public function test_summary_uses_the_complete_active_table_scope(): void
    {
        $this->travelTo('2026-09-15 12:00:00');
        $staff = $this->staff(canExport: true);
        $restaurant = $this->restaurant();
        $rice = $this->ingredient($restaurant, 'Rice');
        $oil = $this->ingredient($restaurant, 'Oil', unit: 'litre');
        $today = $this->movement($rice, $staff, KitchenStockMovement::DIRECTION_IN, 100, '2026-09-15 09:00:00');
        $this->movement($oil, $staff, KitchenStockMovement::DIRECTION_OUT, 40, '2026-09-14 09:00:00');
        $this->movement($rice, $staff, KitchenStockMovement::DIRECTION_IN, 30, '2026-08-15 09:00:00');

        $component = Livewire::actingAs($staff)
            ->test(ListKitchenStockMovements::class)
            ->filterTable('date_preset', 'today')
            ->filterTable('direction', KitchenStockMovement::DIRECTION_IN);
        $summary = $component->instance()->getWidgetData()['stockMovementSummary'] ?? null;

        self::assertSame([
            'movement_count' => 1,
            'ingredient_count' => 1,
            'stock_in_count' => 1,
            'stock_out_count' => 0,
            'stock_in_value' => 100.0,
            'stock_out_value' => 0.0,
            'period_label' => 'Today',
        ], $summary);
        $component->assertCanSeeTableRecords([$today]);

        $this->travelBack();
    }

    public function test_stats_cards_present_counts_and_values_from_the_precomputed_summary(): void
    {
        $widget = new KitchenStockMovementStats;
        $widget->stockMovementSummary = [
            'movement_count' => 7,
            'ingredient_count' => 3,
            'stock_in_count' => 4,
            'stock_out_count' => 3,
            'stock_in_value' => 425.5,
            'stock_out_value' => 175.25,
            'period_label' => 'Last 7 days',
        ];
        $method = new ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);
        $stats = collect($method->invoke($widget))
            ->mapWithKeys(fn (Stat $stat): array => [(string) $stat->getLabel() => $stat]);

        self::assertSame([
            'Movement entries',
            'Ingredients affected',
            'Stock-in value',
            'Stock-out value',
        ], $stats->keys()->all());
        self::assertSame('7', $stats['Movement entries']->getValue());
        self::assertSame('3', $stats['Ingredients affected']->getValue());
        self::assertSame('GHS 425.50', $stats['Stock-in value']->getValue());
        self::assertSame('GHS 175.25', $stats['Stock-out value']->getValue());
        self::assertStringContainsString('Last 7 days', (string) $stats['Movement entries']->getDescription());
    }

    public function test_authorized_csv_export_uses_active_filters_and_neutralizes_spreadsheet_formulas(): void
    {
        self::assertTrue(
            method_exists(ListKitchenStockMovements::class, 'exportCsv'),
            'The filtered stock-movement CSV export has not been implemented.',
        );

        $this->travelTo('2026-09-15 12:30:00');
        $staff = $this->staff(canExport: true);
        $restaurant = $this->restaurant();
        $rice = $this->ingredient($restaurant, 'Export Rice');
        $oil = $this->ingredient($restaurant, 'Excluded Oil', unit: 'litre');
        $this->movement(
            $rice,
            $staff,
            KitchenStockMovement::DIRECTION_IN,
            100,
            '2026-09-15 09:00:00',
            notes: '=HYPERLINK("https://example.test")',
        );
        $this->movement($oil, $staff, KitchenStockMovement::DIRECTION_OUT, 40, '2026-09-15 10:00:00');

        $component = Livewire::actingAs($staff)
            ->test(ListKitchenStockMovements::class)
            ->filterTable('direction', KitchenStockMovement::DIRECTION_IN);
        $response = $component->instance()->exportCsv();

        self::assertInstanceOf(StreamedResponse::class, $response);
        self::assertStringContainsString(
            'kitchen-stock-movements-20260915-123000.csv',
            (string) $response->headers->get('content-disposition'),
        );

        ob_start();
        $response->sendContent();
        $csv = (string) ob_get_clean();

        self::assertStringContainsString('Export Rice', $csv);
        self::assertStringNotContainsString('Excluded Oil', $csv);
        self::assertStringContainsString("'=HYPERLINK", $csv);

        $this->travelBack();
    }

    public function test_view_only_staff_cannot_see_or_invoke_the_csv_export(): void
    {
        $staff = $this->staff(canExport: false);

        Livewire::actingAs($staff)
            ->test(ListKitchenStockMovements::class)
            ->assertDontSee('Export CSV');

        $this->actingAs($staff);

        try {
            (new ListKitchenStockMovements)->exportCsv();
            self::fail('A user without the export permission should not download the stock ledger.');
        } catch (HttpException $exception) {
            self::assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_management_roles_receive_the_export_permission_but_kitchen_staff_remain_view_only(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        foreach (['super_admin', 'admin', 'accountant', 'manager', 'kitchen_manager'] as $roleName) {
            self::assertTrue(
                Role::findByName($roleName, 'web')->hasPermissionTo('export kitchen stock movements'),
                "The {$roleName} role should be able to export the stock ledger.",
            );
        }

        self::assertFalse(Role::findOrCreate('kitchen_staff', 'web')->hasPermissionTo('export kitchen stock movements'));
        self::assertFalse(Role::findOrCreate('receptionist', 'web')->hasPermissionTo('export kitchen stock movements'));
    }

    public function test_migration_provisions_the_export_permission_without_granting_it_to_view_only_roles(): void
    {
        foreach (['super_admin', 'admin', 'accountant', 'manager', 'kitchen_manager'] as $roleName) {
            self::assertTrue(Role::findByName($roleName, 'web')->hasPermissionTo('export kitchen stock movements'));
        }

        self::assertFalse(Role::findOrCreate('kitchen_staff', 'web')->hasPermissionTo('export kitchen stock movements'));
        self::assertFalse(Role::findOrCreate('receptionist', 'web')->hasPermissionTo('export kitchen stock movements'));
    }

    private function staff(bool $canExport): User
    {
        $staff = User::factory()->create([
            'department' => 'kitchen_staff',
            'status' => StaffAccountStatus::Active,
        ]);
        $staff->givePermissionTo(Permission::findOrCreate('view kitchen stock movements', 'web'));

        if ($canExport) {
            $staff->givePermissionTo(Permission::findOrCreate('export kitchen stock movements', 'web'));
        }

        return $staff;
    }

    private function restaurant(): Restaurant
    {
        return Restaurant::query()->create([
            'name' => 'Reporting Kitchen',
            'description' => 'Kitchen used to verify stock movement reporting.',
            'opening_time' => '08:00',
            'closing_time' => '22:00',
        ]);
    }

    private function ingredient(Restaurant $restaurant, string $name, string $unit = 'kg'): Ingredient
    {
        return Ingredient::query()->create([
            'restaurant_id' => $restaurant->id,
            'name' => $name,
            'unit' => $unit,
            'current_stock' => 20,
            'reorder_level' => 5,
            'unit_cost' => 4,
            'is_active' => true,
        ]);
    }

    private function movement(
        Ingredient $ingredient,
        User $staff,
        string $direction,
        float $totalCost,
        string $occurredAt,
        ?string $notes = null,
    ): KitchenStockMovement {
        return KitchenStockMovement::query()->create([
            'ingredient_id' => $ingredient->id,
            'type' => $direction === KitchenStockMovement::DIRECTION_IN
                ? KitchenStockMovement::TYPE_RECEIPT
                : KitchenStockMovement::TYPE_CONSUMPTION,
            'direction' => $direction,
            'quantity' => 2,
            'balance_before' => 10,
            'balance_after' => $direction === KitchenStockMovement::DIRECTION_IN ? 12 : 8,
            'unit_cost' => $totalCost / 2,
            'total_cost' => $totalCost,
            'performed_by' => $staff->id,
            'occurred_at' => $occurredAt,
            'notes' => $notes,
        ]);
    }
}
