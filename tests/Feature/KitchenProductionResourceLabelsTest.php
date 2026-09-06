<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\KitchenProductions\KitchenProductionResource;
use App\Filament\Admin\Resources\KitchenProductions\Pages\ListKitchenProductions;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class KitchenProductionResourceLabelsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_resource_uses_consistent_module_and_batch_labels(): void
    {
        self::assertSame('Kitchen Production', KitchenProductionResource::getNavigationLabel());
        self::assertSame('production batch', KitchenProductionResource::getModelLabel());
        self::assertSame('production batches', KitchenProductionResource::getPluralModelLabel());
        self::assertSame('Production Batch', KitchenProductionResource::getTitleCaseModelLabel());
        self::assertSame('Production Batches', KitchenProductionResource::getTitleCasePluralModelLabel());
    }

    public function test_list_page_uses_batch_wording_for_the_heading_and_create_action(): void
    {
        $staff = User::factory()->create(['department' => 'kitchen_staff']);
        $staff->givePermissionTo(Permission::findOrCreate('manage kitchen production', 'web'));

        Livewire::actingAs($staff)
            ->test(ListKitchenProductions::class)
            ->assertSeeText('Production Batches')
            ->assertSeeText('New production batch');
    }
}
