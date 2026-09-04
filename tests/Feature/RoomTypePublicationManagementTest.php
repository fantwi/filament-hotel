<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\RoomTypes\Pages\ListRoomTypes;
use App\Models\RoomType;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoomTypePublicationManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_admin_can_toggle_room_type_publication_from_the_list(): void
    {
        $admin = $this->createStaff('admin', 'admin');
        $roomType = $this->createRoomType($admin, true);

        Livewire::actingAs($admin)
            ->test(ListRoomTypes::class)
            ->call('updateTableColumnState', 'is_published', (string) $roomType->getKey(), false);

        self::assertFalse($roomType->fresh()->is_published);
    }

    public function test_receptionist_cannot_forge_a_room_type_publication_toggle(): void
    {
        $admin = $this->createStaff('admin', 'admin');
        $receptionist = $this->createStaff('receptionist', 'reception');
        $roomType = $this->createRoomType($admin, true);

        Livewire::actingAs($receptionist)
            ->test(ListRoomTypes::class)
            ->assertTableColumnExists(
                'is_published',
                fn ($column): bool => $column instanceof ToggleColumn && $column->isDisabled(),
                $roomType,
            )
            ->call('updateTableColumnState', 'is_published', (string) $roomType->getKey(), false);

        self::assertTrue($roomType->fresh()->is_published);
    }

    public function test_admin_can_publish_and_unpublish_selected_room_types(): void
    {
        $admin = $this->createStaff('admin', 'admin');
        $first = $this->createRoomType($admin, false, 'First Draft Room');
        $second = $this->createRoomType($admin, false, 'Second Draft Room');

        $publishComponent = Livewire::actingAs($admin)->test(ListRoomTypes::class);

        self::assertNotNull($publishComponent->instance()->getTable()->getBulkAction('publish'));

        $publishComponent->callTableBulkAction('publish', [$first, $second]);

        self::assertTrue($first->fresh()->is_published);
        self::assertTrue($second->fresh()->is_published);

        $unpublishComponent = Livewire::actingAs($admin)->test(ListRoomTypes::class);

        self::assertNotNull($unpublishComponent->instance()->getTable()->getBulkAction('unpublish'));

        $unpublishComponent->callTableBulkAction('unpublish', [$first, $second]);

        self::assertFalse($first->fresh()->is_published);
        self::assertFalse($second->fresh()->is_published);
    }

    public function test_receptionist_cannot_access_publication_bulk_actions(): void
    {
        $receptionist = $this->createStaff('receptionist', 'reception');

        $component = Livewire::actingAs($receptionist)->test(ListRoomTypes::class);

        self::assertNotNull($component->instance()->getTable()->getBulkAction('publish'));
        self::assertNotNull($component->instance()->getTable()->getBulkAction('unpublish'));

        $component
            ->assertTableBulkActionHidden('publish')
            ->assertTableBulkActionHidden('unpublish');
    }

    private function createStaff(string $role, string $department): User
    {
        $user = User::factory()->create(['department' => $department]);
        $user->assignRole(Role::findOrCreate($role, 'web'));

        return $user;
    }

    private function createRoomType(User $creator, bool $published, string $name = 'Publication Test Room'): RoomType
    {
        return RoomType::query()->create([
            'name' => $name,
            'price_per_night' => 300,
            'capacity' => 2,
            'description' => 'A room type used to verify list publication controls.',
            'is_published' => $published,
            'created_by' => $creator->id,
        ]);
    }
}
