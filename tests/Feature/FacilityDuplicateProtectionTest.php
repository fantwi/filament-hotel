<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Facilities\Pages\CreateFacility;
use App\Filament\Admin\Resources\Facilities\Pages\EditFacility;
use App\Models\Facility;
use App\Models\Restaurant;
use App\Models\RoomType;
use App\Models\User;
use Database\Seeders\RestaurantFacilitySeeder;
use Filament\Facades\Filament;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FacilityDuplicateProtectionTest extends TestCase
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

    public function test_create_form_rejects_an_equivalent_facility_name(): void
    {
        Facility::query()->create(['name' => 'Air Conditioning']);

        Livewire::actingAs($this->admin)
            ->test(CreateFacility::class)
            ->fillForm([
                'name' => '  AIR CONDITIONING  ',
                'is_published' => false,
            ])
            ->call('create')
            ->assertHasFormErrors(['name']);

        $this->assertDatabaseCount('facilities', 1);
    }

    public function test_edit_form_keeps_the_current_facility_name_after_normalization(): void
    {
        $facility = Facility::query()->create(['name' => 'Air Conditioning']);

        Livewire::actingAs($this->admin)
            ->test(EditFacility::class, ['record' => $facility->id])
            ->fillForm([
                'name' => '  Air   Conditioning  ',
                'icon' => 'snowflake',
                'is_published' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        self::assertSame('Air Conditioning', $facility->fresh()->name);
    }

    public function test_database_rejects_an_exact_duplicate_facility_name(): void
    {
        DB::table('facilities')->insert([
            'name' => 'Parking',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        DB::table('facilities')->insert([
            'name' => 'Parking',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_migration_merges_air_conditioning_aliases_and_preserves_relationships(): void
    {
        $migrationPath = database_path('migrations/2026_09_04_000200_merge_duplicate_facilities.php');

        self::assertFileExists($migrationPath);

        $migration = require $migrationPath;
        $migration->down();

        $canonicalId = DB::table('facilities')->insertGetId([
            'name' => 'Air Conditioning',
            'icon' => 'snowflake',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $aliasId = DB::table('facilities')->insertGetId([
            'name' => 'Air Conditioned',
            'icon' => 'snowflake',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $roomType = RoomType::query()->create([
            'name' => 'Migration Test Room',
            'price_per_night' => 250,
            'capacity' => 2,
            'description' => 'A room type used to verify facility consolidation.',
            'is_published' => false,
        ]);
        $restaurant = Restaurant::query()->create([
            'name' => 'Migration Test Restaurant',
            'description' => 'A restaurant used to verify facility consolidation.',
            'opening_time' => '07:00',
            'closing_time' => '22:00',
            'is_published' => false,
        ]);

        DB::table('facility_room_type')->insert([
            [
                'facility_id' => $canonicalId,
                'room_type_id' => $roomType->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'facility_id' => $aliasId,
                'room_type_id' => $roomType->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        DB::table('facility_restaurant')->insert([
            'facility_id' => $aliasId,
            'restaurant_id' => $restaurant->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration->up();

        $this->assertDatabaseHas('facilities', [
            'id' => $canonicalId,
            'name' => 'Air Conditioning',
        ]);
        $this->assertDatabaseMissing('facilities', ['id' => $aliasId]);
        self::assertSame(1, DB::table('facility_room_type')->where('room_type_id', $roomType->id)->count());
        $this->assertDatabaseHas('facility_room_type', [
            'facility_id' => $canonicalId,
            'room_type_id' => $roomType->id,
        ]);
        $this->assertDatabaseHas('facility_restaurant', [
            'facility_id' => $canonicalId,
            'restaurant_id' => $restaurant->id,
        ]);
    }

    public function test_restaurant_facility_seeder_uses_the_canonical_air_conditioning_name(): void
    {
        $restaurant = Restaurant::query()->create([
            'name' => 'Seeder Test Restaurant',
            'description' => 'A restaurant used to verify canonical facility seeding.',
            'opening_time' => '07:00',
            'closing_time' => '22:00',
            'is_published' => false,
        ]);

        $this->seed(RestaurantFacilitySeeder::class);

        $canonical = Facility::query()->where('name', 'Air Conditioning')->first();

        self::assertNotNull($canonical);
        self::assertFalse(Facility::query()->where('name', 'Air Conditioned')->exists());
        self::assertTrue($restaurant->facilities()->whereKey($canonical->id)->exists());
    }
}
