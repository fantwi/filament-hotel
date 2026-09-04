<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\RoomTypes\RoomTypeResource;
use App\Filament\Admin\Resources\RoomTypes\Schemas\RoomTypeInfolist;
use App\Models\Facility;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoomTypeDetailsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_room_type_details_schema_exposes_responsive_operational_media_and_audit_context(): void
    {
        $livewire = new class extends Component implements HasSchemas
        {
            use InteractsWithSchemas;
        };
        $schema = RoomTypeInfolist::configure(Schema::make($livewire));

        self::assertSame(['default' => 1, 'lg' => 3], $schema->getColumns());

        foreach ([
            'name',
            'price_per_night',
            'capacity',
            'rooms_count',
            'description',
            'image',
            'gallery',
            'facilities.name',
            'publication_status',
            'creator.name',
            'created_at',
            'updated_at',
        ] as $componentName) {
            self::assertNotNull($schema->getComponent($componentName), "Missing [{$componentName}] from the Room Type details schema.");
        }

        $price = $schema->getComponent('price_per_night');
        $coverImage = $schema->getComponent('image');
        $gallery = $schema->getComponent('gallery');
        $roomCount = $schema->getComponent('rooms_count');

        self::assertInstanceOf(TextEntry::class, $price);
        self::assertTrue($price->isMoney());
        self::assertStringContainsString('GHS', (string) $price->formatState(350));
        self::assertStringContainsString('350.00', (string) $price->formatState(350));
        self::assertInstanceOf(ImageEntry::class, $coverImage);
        self::assertSame('public', $coverImage->getDiskName());
        self::assertSame('public', $coverImage->getVisibility());
        self::assertInstanceOf(ImageEntry::class, $gallery);
        self::assertSame('public', $gallery->getDiskName());
        self::assertSame('public', $gallery->getVisibility());
        self::assertInstanceOf(TextEntry::class, $roomCount);
    }

    public function test_authorized_admin_sees_complete_room_type_details(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('room-types/executive-suite.jpg', 'cover');
        Storage::disk('public')->put('room-types/gallery/suite-bedroom.jpg', 'gallery');

        $admin = User::factory()->create([
            'first_name' => 'Ava',
            'last_name' => 'Admin',
            'department' => 'admin',
        ]);
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        $roomType = RoomType::query()->create([
            'name' => 'Executive Suite',
            'price_per_night' => 350,
            'capacity' => 2,
            'description' => 'A spacious suite for executive guests.',
            'image' => 'room-types/executive-suite.jpg',
            'gallery' => ['room-types/gallery/suite-bedroom.jpg'],
            'is_published' => true,
            'created_by' => $admin->id,
        ]);
        $facility = Facility::query()->create([
            'name' => 'Ocean View',
            'is_published' => true,
            'created_by' => $admin->id,
        ]);
        $roomType->facilities()->attach($facility);
        Room::query()->create([
            'room_type_id' => $roomType->id,
            'room_number' => 'E101',
            'status' => 'available',
        ]);
        Room::query()->create([
            'room_type_id' => $roomType->id,
            'room_number' => 'E102',
            'status' => 'available',
        ]);

        $this->actingAs($admin)
            ->get(RoomTypeResource::getUrl('view', ['record' => $roomType]))
            ->assertOk()
            ->assertSeeText('Executive Suite')
            ->assertSeeText('GHS')
            ->assertSeeText('350.00')
            ->assertSeeText('2 guests')
            ->assertSeeText('2 rooms')
            ->assertSeeText('Ocean View')
            ->assertSeeText('Published')
            ->assertSeeText('Ava Admin')
            ->assertSee('room-types/executive-suite.jpg', escape: false)
            ->assertSee('room-types/gallery/suite-bedroom.jpg', escape: false);
    }
}
