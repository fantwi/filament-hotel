<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\KitchenProductions\Pages\CreateKitchenProduction;
use App\Filament\Admin\Resources\KitchenProductions\Pages\EditKitchenProduction;
use App\Models\KitchenProduction;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class KitchenProductionYieldSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_quantity_fields_show_the_selected_production_unit_with_correct_pluralization(): void
    {
        $menuItem = $this->menuItem('tray');

        $this->page()
            ->fillForm([
                'menu_item_id' => $menuItem->id,
                'quantity_produced' => 10,
                'quantity_wasted' => 1,
            ])
            ->assertSchemaComponentExists(
                'quantity_produced',
                checkComponentUsing: fn ($component): bool => $component instanceof TextInput
                    && $component->getSuffixLabel() === 'trays',
            )
            ->assertSchemaComponentExists(
                'quantity_wasted',
                checkComponentUsing: fn ($component): bool => $component instanceof TextInput
                    && $component->getSuffixLabel() === 'tray',
            );
    }

    public function test_waste_quantity_uses_live_debounced_binding_for_the_yield_summary(): void
    {
        $this->page()
            ->assertSchemaComponentExists(
                'quantity_wasted',
                checkComponentUsing: fn ($component): bool => $component instanceof TextInput
                    && $component->isLiveDebounced()
                    && $component->getNormalizedLiveDebounce() === 500,
            );
    }

    public function test_live_yield_summary_uses_a_responsive_mobile_first_layout(): void
    {
        $this->page()
            ->assertSchemaComponentExists(
                'live_yield_summary',
                checkComponentUsing: fn ($component): bool => $component instanceof Section
                    && $component->getColumns() === ['default' => 1, 'sm' => 2, 'lg' => 4],
            );
    }

    public function test_live_summary_calculates_produced_wasted_net_yield_and_waste_percentage(): void
    {
        $menuItem = $this->menuItem('portion');

        $page = $this->page()->fillForm([
            'menu_item_id' => $menuItem->id,
            'quantity_produced' => 80,
            'quantity_wasted' => 5,
        ]);

        $this->assertSummary($page, 'produced_yield_summary', '80.000 portions', 'info');
        $this->assertSummary($page, 'wasted_yield_summary', '5.000 portions', 'warning');
        $this->assertSummary($page, 'net_yield_summary', '75.000 portions', 'success');
        $this->assertSummary($page, 'waste_rate_summary', '6.25%', 'info');
    }

    public function test_live_summary_handles_zero_production_without_dividing_by_zero(): void
    {
        $menuItem = $this->menuItem('bottle');

        $page = $this->page()->fillForm([
            'menu_item_id' => $menuItem->id,
            'quantity_produced' => 0,
            'quantity_wasted' => 0,
        ]);

        $this->assertSummary($page, 'net_yield_summary', '0.000 bottles', 'success');
        $this->assertSummary($page, 'waste_rate_summary', '—', 'gray');
    }

    public function test_live_summary_flags_waste_above_production_before_submission(): void
    {
        $menuItem = $this->menuItem('piece');

        $page = $this->page()->fillForm([
            'menu_item_id' => $menuItem->id,
            'quantity_produced' => 10,
            'quantity_wasted' => 12,
        ]);

        $this->assertSummary($page, 'wasted_yield_summary', '12.000 pieces', 'danger');
        $this->assertSummary($page, 'net_yield_summary', 'Invalid waste quantity', 'danger');
        $this->assertSummary($page, 'waste_rate_summary', 'Check waste quantity', 'danger');
    }

    public function test_save_time_validation_rejects_waste_above_production(): void
    {
        $restaurant = $this->restaurant();
        $menuItem = $this->menuItem('portion');

        $this->page()
            ->fillForm([
                'restaurant_id' => $restaurant->id,
                'menu_item_id' => $menuItem->id,
                'production_date' => today()->toDateString(),
                'quantity_produced' => 10,
                'quantity_wasted' => 12,
            ])
            ->call('create')
            ->assertHasFormErrors(['quantity_wasted']);

        self::assertDatabaseCount('kitchen_productions', 0);
    }

    public function test_edit_form_hydrates_unit_suffixes_and_yield_summary_from_the_saved_batch(): void
    {
        $restaurant = $this->restaurant();
        $menuItem = $this->menuItem('tray');
        $production = KitchenProduction::query()->create([
            'restaurant_id' => $restaurant->id,
            'menu_item_id' => $menuItem->id,
            'production_date' => today(),
            'quantity_produced' => 10,
            'quantity_wasted' => 1,
        ]);

        $page = Livewire::actingAs($this->kitchenStaff())
            ->test(EditKitchenProduction::class, ['record' => $production->id])
            ->assertSchemaComponentExists(
                'quantity_produced',
                checkComponentUsing: fn ($component): bool => $component instanceof TextInput
                    && $component->getSuffixLabel() === 'trays',
            )
            ->assertSchemaComponentExists(
                'quantity_wasted',
                checkComponentUsing: fn ($component): bool => $component instanceof TextInput
                    && $component->getSuffixLabel() === 'tray',
            );

        $this->assertSummary($page, 'produced_yield_summary', '10.000 trays', 'info');
        $this->assertSummary($page, 'wasted_yield_summary', '1.000 tray', 'warning');
        $this->assertSummary($page, 'net_yield_summary', '9.000 trays', 'success');
        $this->assertSummary($page, 'waste_rate_summary', '10.00%', 'info');
    }

    private function page(): Testable
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        return Livewire::actingAs($this->kitchenStaff())->test(CreateKitchenProduction::class);
    }

    private function kitchenStaff(): User
    {
        $staff = User::factory()->create(['department' => 'kitchen_manager']);
        $staff->givePermissionTo(Permission::findOrCreate('manage kitchen production', 'web'));

        return $staff;
    }

    private function restaurant(): Restaurant
    {
        return Restaurant::query()->create([
            'name' => 'Yield Summary Restaurant '.str()->random(8),
            'description' => 'Kitchen production yield summary tests.',
            'opening_time' => '08:00',
            'closing_time' => '22:00',
        ]);
    }

    private function menuItem(string $productionUnit): MenuItem
    {
        $category = MenuCategory::query()->create([
            'name' => 'Yield Summary '.str()->random(8),
            'slug' => str()->random(16),
        ]);

        return MenuItem::query()->create([
            'menu_category_id' => $category->id,
            'name' => 'Yield Meal '.str()->random(8),
            'slug' => str()->random(16),
            'price' => 25,
            'tracks_kitchen_production' => true,
            'production_unit' => $productionUnit,
            'inventory_consumption_mode' => 'none',
        ]);
    }

    private function assertSummary(Testable $page, string $name, string $state, string $color): void
    {
        $page->assertSchemaComponentExists(
            "live_yield_summary.{$name}",
            checkComponentUsing: fn ($component): bool => $component instanceof TextEntry
                && $component->getState() === $state
                && $component->getColor($component->getState()) === $color,
        );
    }
}
