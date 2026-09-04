<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\RoomTypes\Pages\CreateRoomType;
use App\Filament\Admin\Resources\RoomTypes\Pages\EditRoomType;
use App\Filament\Admin\Resources\RoomTypes\Pages\ListRoomTypes;
use App\Filament\Admin\Resources\RoomTypes\Pages\ViewRoomType;
use App\Filament\Admin\Resources\RoomTypes\RoomTypeResource;
use App\Models\Facility;
use App\Models\RoomType;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoomTypeResourceRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    #[DataProvider('roomTypeManagers')]
    public function test_authorized_management_roles_can_open_every_room_type_workflow(
        string $department,
    ): void {
        $staff = $this->createStaff($department);
        $roomType = $this->createRoomType($staff, ['name' => "{$department} workflow room"]);

        $this->actingAs($staff);

        $this->get(RoomTypeResource::getUrl('index'))->assertOk();
        $this->get(RoomTypeResource::getUrl('create'))->assertOk();
        $this->get(RoomTypeResource::getUrl('view', ['record' => $roomType]))->assertOk();
        $this->get(RoomTypeResource::getUrl('edit', ['record' => $roomType]))->assertOk();
    }

    public function test_receptionist_has_read_only_room_type_access_in_ui_and_direct_routes(): void
    {
        $admin = $this->createStaff('admin');
        $receptionist = $this->createStaff('reception');
        $roomType = $this->createRoomType($admin);

        $this->actingAs($receptionist);

        $this->get(RoomTypeResource::getUrl('index'))->assertOk();
        $this->get(RoomTypeResource::getUrl('view', ['record' => $roomType]))->assertOk();
        $this->get(RoomTypeResource::getUrl('create'))->assertForbidden();
        $this->get(RoomTypeResource::getUrl('edit', ['record' => $roomType]))->assertForbidden();

        Livewire::actingAs($receptionist)
            ->test(ListRoomTypes::class)
            ->assertActionHidden('create')
            ->assertTableActionVisible('view', $roomType)
            ->assertTableActionHidden('edit', $roomType)
            ->assertTableBulkActionHidden('publish')
            ->assertTableBulkActionHidden('unpublish');

        Livewire::actingAs($receptionist)
            ->test(ViewRoomType::class, ['record' => $roomType->getKey()])
            ->assertActionHidden('edit');
    }

    public function test_unrelated_staff_cannot_open_or_discover_room_types(): void
    {
        $admin = $this->createStaff('admin');
        $kitchenStaff = $this->createStaff('kitchen_staff');
        $roomType = $this->createRoomType($admin);

        $this->actingAs($kitchenStaff);

        self::assertFalse(RoomTypeResource::shouldRegisterNavigation());
        self::assertFalse(RoomTypeResource::canViewAny());
        $this->get(RoomTypeResource::getUrl('index'))->assertForbidden();
        $this->get(RoomTypeResource::getUrl('view', ['record' => $roomType]))->assertForbidden();
    }

    public function test_room_type_list_exposes_published_and_owned_drafts_without_leaking_other_drafts(): void
    {
        $admin = $this->createStaff('admin');
        $otherAdmin = $this->createStaff('admin');
        $published = $this->createRoomType($otherAdmin, [
            'name' => 'Published room',
            'is_published' => true,
        ]);
        $ownedDraft = $this->createRoomType($admin, [
            'name' => 'Owned draft',
            'is_published' => false,
        ]);
        $otherDraft = $this->createRoomType($otherAdmin, [
            'name' => 'Other staff draft',
            'is_published' => false,
        ]);

        Livewire::actingAs($admin)
            ->test(ListRoomTypes::class)
            ->assertCanSeeTableRecords([$ownedDraft, $published])
            ->assertCanNotSeeTableRecords([$otherDraft]);
    }

    public function test_admin_can_create_a_draft_with_facilities_and_audit_ownership(): void
    {
        $admin = $this->createStaff('admin');
        $facility = $this->createFacility($admin, 'Balcony');

        Livewire::actingAs($admin)
            ->test(CreateRoomType::class)
            ->fillForm([
                'name' => 'Garden Suite',
                'price_per_night' => 420,
                'capacity' => 3,
                'description' => 'A quiet suite overlooking the hotel gardens.',
                'facilities' => [$facility->getKey()],
                'is_published' => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $roomType = RoomType::query()->where('name', 'Garden Suite')->firstOrFail();

        self::assertSame($admin->getKey(), $roomType->created_by);
        self::assertFalse($roomType->is_published);
        $this->assertDatabaseHas('facility_room_type', [
            'facility_id' => $facility->getKey(),
            'room_type_id' => $roomType->getKey(),
        ]);
    }

    public function test_admin_can_edit_details_and_replace_facilities_without_changing_the_creator(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('room-types/regression-suite.jpg', 'cover');

        $creator = $this->createStaff('super_admin');
        $admin = $this->createStaff('admin');
        $oldFacility = $this->createFacility($creator, 'City View');
        $newFacility = $this->createFacility($admin, 'Accessible Bathroom');
        $roomType = $this->createRoomType($creator, ['is_published' => true]);
        $roomType->facilities()->attach($oldFacility);

        Livewire::actingAs($admin)
            ->test(EditRoomType::class, ['record' => $roomType->getKey()])
            ->fillForm([
                'name' => 'Updated Executive Suite',
                'price_per_night' => 575,
                'capacity' => 4,
                'description' => 'Updated details for an accessible executive stay.',
                'facilities' => [$newFacility->getKey()],
                'is_published' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $roomType->refresh();

        self::assertSame('Updated Executive Suite', $roomType->name);
        self::assertEqualsWithDelta(575.0, (float) $roomType->price_per_night, 0.001);
        self::assertSame(4, $roomType->capacity);
        self::assertSame($creator->getKey(), $roomType->created_by);
        self::assertSame([$newFacility->getKey()], $roomType->facilities()->pluck('facilities.id')->all());
    }

    public function test_admin_list_exposes_management_actions_for_an_eligible_room_type(): void
    {
        $admin = $this->createStaff('admin');
        $roomType = $this->createRoomType($admin);

        Livewire::actingAs($admin)
            ->test(ListRoomTypes::class)
            ->assertActionVisible('create')
            ->assertTableActionVisible('view', $roomType)
            ->assertTableActionVisible('edit', $roomType)
            ->assertTableBulkActionVisible('publish')
            ->assertTableBulkActionVisible('unpublish')
            ->assertTableBulkActionVisible('delete');
    }

    public static function roomTypeManagers(): array
    {
        return [
            'super admin' => ['super_admin'],
            'admin' => ['admin'],
        ];
    }

    private function createStaff(string $department): User
    {
        return User::factory()->create(['department' => $department]);
    }

    private function createFacility(User $creator, string $name): Facility
    {
        return Facility::query()->create([
            'name' => $name,
            'is_published' => true,
            'created_by' => $creator->getKey(),
        ]);
    }

    private function createRoomType(User $creator, array $overrides = []): RoomType
    {
        return RoomType::query()->create(array_merge([
            'name' => 'Regression Suite',
            'price_per_night' => 350,
            'capacity' => 2,
            'description' => 'A complete room type used by the resource regression suite.',
            'image' => 'room-types/regression-suite.jpg',
            'is_published' => true,
            'created_by' => $creator->getKey(),
        ], $overrides));
    }
}
