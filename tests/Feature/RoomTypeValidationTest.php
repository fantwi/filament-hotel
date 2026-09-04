<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\RoomTypes\Pages\CreateRoomType;
use App\Filament\Admin\Resources\RoomTypes\Pages\EditRoomType;
use App\Models\RoomType;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoomTypeValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->admin = User::factory()->create(['department' => 'admin']);
        $this->admin->assignRole(Role::findOrCreate('admin', 'web'));
    }

    #[DataProvider('invalidRoomTypeValues')]
    public function test_create_form_rejects_invalid_room_type_values(string $field, mixed $value, string $rule): void
    {
        $data = $this->validFormData();
        $data[$field] = $value;

        Livewire::actingAs($this->admin)
            ->test(CreateRoomType::class)
            ->fillForm($data)
            ->call('create')
            ->assertHasFormErrors([$field => $rule]);

        $this->assertDatabaseCount('room_types', 0);
    }

    public function test_create_form_rejects_a_duplicate_room_type_name(): void
    {
        $this->createRoomType(['name' => 'Deluxe Suite']);

        Livewire::actingAs($this->admin)
            ->test(CreateRoomType::class)
            ->fillForm($this->validFormData(['name' => 'Deluxe Suite']))
            ->call('create')
            ->assertHasFormErrors(['name' => 'unique']);

        $this->assertDatabaseCount('room_types', 1);
    }

    public function test_edit_form_allows_a_room_type_to_keep_its_current_name(): void
    {
        $roomType = $this->createRoomType(['name' => 'Standard Room']);

        Livewire::actingAs($this->admin)
            ->test(EditRoomType::class, ['record' => $roomType->id])
            ->fillForm($this->validFormData(['name' => 'Standard Room']))
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('room_types', [
            'id' => $roomType->id,
            'name' => 'Standard Room',
        ]);
    }

    public function test_create_form_trims_a_valid_room_type_name(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CreateRoomType::class)
            ->fillForm($this->validFormData(['name' => '  Executive Suite  ']))
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('room_types', [
            'name' => 'Executive Suite',
            'price_per_night' => 0.01,
            'capacity' => 1,
        ]);
    }

    public function test_database_rejects_duplicate_room_type_names(): void
    {
        $this->createRoomType(['name' => 'Family Room']);

        $this->expectException(QueryException::class);

        $this->createRoomType(['name' => 'Family Room']);
    }

    public static function invalidRoomTypeValues(): array
    {
        return [
            'name exceeds database length' => ['name', str_repeat('R', 256), 'max'],
            'zero nightly price' => ['price_per_night', 0, 'min'],
            'negative nightly price' => ['price_per_night', -1, 'min'],
            'nightly price has sub-pesewa precision' => ['price_per_night', 10.001, 'multiple_of'],
            'zero capacity' => ['capacity', 0, 'min'],
            'fractional capacity' => ['capacity', 1.5, 'integer'],
        ];
    }

    private function validFormData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Valid room type',
            'price_per_night' => 0.01,
            'capacity' => 1,
            'description' => 'A valid room type used by the Filament form test.',
            'is_published' => false,
        ], $overrides);
    }

    private function createRoomType(array $overrides = []): RoomType
    {
        return RoomType::query()->create(array_merge([
            'name' => 'Existing room type',
            'price_per_night' => 250,
            'capacity' => 2,
            'description' => 'An existing room type used by the validation test.',
            'is_published' => true,
            'created_by' => $this->admin->id,
        ], $overrides));
    }
}
