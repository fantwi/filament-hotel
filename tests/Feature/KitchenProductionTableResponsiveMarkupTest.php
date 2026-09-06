<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\KitchenProductions\Pages\ListKitchenProductions;
use App\Models\KitchenProduction;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\User;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class KitchenProductionTableResponsiveMarkupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_rendered_table_hides_the_mobile_summary_header_without_hiding_its_mobile_cell(): void
    {
        $staff = User::factory()->create(['department' => 'kitchen_staff']);
        $staff->givePermissionTo(Permission::findOrCreate('manage kitchen production', 'web'));
        $restaurant = Restaurant::query()->create([
            'name' => 'Responsive Test Kitchen',
            'description' => 'Kitchen used to verify responsive production table markup.',
            'opening_time' => '08:00',
            'closing_time' => '22:00',
        ]);
        $category = MenuCategory::query()->create([
            'name' => 'Prepared Meals',
            'slug' => 'prepared-meals',
        ]);
        $menuItem = MenuItem::query()->create([
            'menu_category_id' => $category->id,
            'name' => 'Responsive Jollof',
            'slug' => 'responsive-jollof',
            'price' => 25,
            'tracks_kitchen_production' => true,
            'production_unit' => 'portion',
            'inventory_consumption_mode' => 'none',
        ]);

        KitchenProduction::query()->create([
            'restaurant_id' => $restaurant->id,
            'menu_item_id' => $menuItem->id,
            'produced_by' => $staff->id,
            'batch_reference' => 'KP-RESPONSIVE-001',
            'production_date' => '2026-09-06',
            'quantity_produced' => 20,
            'quantity_wasted' => 1,
        ]);

        $html = Livewire::actingAs($staff)
            ->test(ListKitchenProductions::class)
            ->html();
        $dom = new DOMDocument;
        $dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($dom);
        $header = $xpath->query('//th[contains(concat(" ", normalize-space(@class), " "), " fi-ta-header-cell-mobile-summary ")]')?->item(0);
        $cell = $xpath->query('//td[contains(concat(" ", normalize-space(@class), " "), " fi-ta-cell-mobile-summary ")]')?->item(0);

        self::assertInstanceOf(DOMElement::class, $header);
        self::assertTrue($header->hasAttribute('hidden'), 'The mobile-only summary heading must not occupy a desktop table column.');
        self::assertInstanceOf(DOMElement::class, $cell);
        self::assertFalse($cell->hasAttribute('hidden'), 'The summary cell must remain available in the mobile stacked layout.');
        self::assertStringContainsString('md:fi-hidden', $cell->getAttribute('class'));
        self::assertStringContainsString('Production Batch', $cell->textContent);
        self::assertStringContainsString('Responsive Jollof', $cell->textContent);
    }
}
