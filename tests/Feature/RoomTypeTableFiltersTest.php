<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\RoomTypes\Pages\ListRoomTypes;
use App\Models\Facility;
use App\Models\RoomType;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoomTypeTableFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_publication_filter_separates_published_and_draft_room_types(): void
    {
        $admin = $this->createAdmin();
        $published = $this->createRoomType($admin, 'Published Suite', 2, true);
        $draft = $this->createRoomType($admin, 'Draft Suite', 2, false);

        Livewire::actingAs($admin)
            ->test(ListRoomTypes::class)
            ->filterTable('is_published', true)
            ->assertCanSeeTableRecords([$published])
            ->assertCanNotSeeTableRecords([$draft])
            ->filterTable('is_published', false)
            ->assertCanSeeTableRecords([$draft])
            ->assertCanNotSeeTableRecords([$published]);
    }

    public function test_capacity_filter_uses_exact_and_four_plus_guest_groups(): void
    {
        $admin = $this->createAdmin();
        $single = $this->createRoomType($admin, 'Single Room', 1);
        $double = $this->createRoomType($admin, 'Double Room', 2);
        $triple = $this->createRoomType($admin, 'Triple Room', 3);
        $family = $this->createRoomType($admin, 'Family Room', 5);

        Livewire::actingAs($admin)
            ->test(ListRoomTypes::class)
            ->filterTable('capacity', '2')
            ->assertCanSeeTableRecords([$double])
            ->assertCanNotSeeTableRecords([$single, $triple, $family])
            ->filterTable('capacity', '4+')
            ->assertCanSeeTableRecords([$family])
            ->assertCanNotSeeTableRecords([$single, $double, $triple]);
    }

    public function test_facility_filter_returns_only_room_types_with_the_selected_facility(): void
    {
        $admin = $this->createAdmin();
        $oceanView = Facility::query()->create([
            'name' => 'Ocean View',
            'is_published' => true,
            'created_by' => $admin->id,
        ]);
        $accessible = Facility::query()->create([
            'name' => 'Accessible Bathroom',
            'is_published' => true,
            'created_by' => $admin->id,
        ]);
        $suite = $this->createRoomType($admin, 'Ocean Suite', 2);
        $standard = $this->createRoomType($admin, 'Accessible Standard Room', 2);
        $suite->facilities()->attach($oceanView);
        $standard->facilities()->attach($accessible);

        Livewire::actingAs($admin)
            ->test(ListRoomTypes::class)
            ->filterTable('facility', $oceanView->getKey())
            ->assertCanSeeTableRecords([$suite])
            ->assertCanNotSeeTableRecords([$standard]);
    }

    public function test_room_types_are_sorted_alphabetically_by_default(): void
    {
        $admin = $this->createAdmin();
        $zulu = $this->createRoomType($admin, 'Zulu Suite', 2);
        $alpha = $this->createRoomType($admin, 'Alpha Room', 2);

        Livewire::actingAs($admin)
            ->test(ListRoomTypes::class)
            ->assertCanSeeTableRecords([$alpha, $zulu], inOrder: true);
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->create(['department' => 'admin']);
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        return $admin;
    }

    private function createRoomType(
        User $creator,
        string $name,
        int $capacity,
        bool $isPublished = true,
    ): RoomType {
        return RoomType::query()->create([
            'name' => $name,
            'price_per_night' => 250,
            'capacity' => $capacity,
            'description' => "Test details for {$name}.",
            'is_published' => $isPublished,
            'created_by' => $creator->id,
        ]);
    }
}
