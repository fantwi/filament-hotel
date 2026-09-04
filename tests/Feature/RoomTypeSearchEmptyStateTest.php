<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\RoomTypes\Pages\ListRoomTypes;
use App\Models\RoomType;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoomTypeSearchEmptyStateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_unmatched_search_explains_the_empty_result_and_can_restore_room_types(): void
    {
        $admin = User::factory()->create(['department' => 'admin']);
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        $roomType = RoomType::query()->create([
            'name' => 'Executive Suite',
            'price_per_night' => 450,
            'capacity' => 2,
            'description' => 'A room type that should return after clearing the search.',
            'is_published' => true,
            'created_by' => $admin->id,
        ]);

        Livewire::actingAs($admin)
            ->test(ListRoomTypes::class)
            ->set('tableSearch', 'DOES-NOT-EXIST')
            ->assertCanNotSeeTableRecords([$roomType])
            ->assertSeeText('No room types match your search')
            ->assertSeeText('Clear the search to return to all room types.')
            ->assertTableEmptyStateActionsExistInOrder(['clearSearch'])
            ->callAction(TestAction::make('clearSearch')->table())
            ->assertSet('tableSearch', '')
            ->assertCanSeeTableRecords([$roomType]);
    }
}
