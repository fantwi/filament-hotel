<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Consolidate equivalent facilities before enforcing unique names.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::table('facilities')
                ->orderBy('id')
                ->get()
                ->groupBy(fn (object $facility): string => $this->normalizedName((string) $facility->name))
                ->each(fn (Collection $facilities, string $normalizedName) => $this->mergeFacilities($facilities, $normalizedName));
        });

        Schema::table('facilities', function (Blueprint $table): void {
            $table->unique('name');
        });
    }

    /**
     * Remove the database uniqueness constraint when rolling back.
     */
    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $table): void {
            $table->dropUnique(['name']);
        });
    }

    /**
     * Merge one normalized-name group into a single canonical facility.
     *
     * @param  Collection<int, object>  $facilities
     */
    private function mergeFacilities(Collection $facilities, string $normalizedName): void
    {
        $canonical = $facilities->first(
            fn (object $facility): bool => Str::lower(Str::squish((string) $facility->name)) === $normalizedName,
        ) ?? $facilities->first();

        foreach ($facilities as $facility) {
            if ($facility->id === $canonical->id) {
                continue;
            }

            $this->moveRelationships('facility_room_type', 'room_type_id', $facility->id, $canonical->id);
            $this->moveRelationships('facility_restaurant', 'restaurant_id', $facility->id, $canonical->id);

            DB::table('facilities')->where('id', $facility->id)->delete();
        }

        DB::table('facilities')
            ->where('id', $canonical->id)
            ->update(['name' => $this->displayName((string) $canonical->name)]);
    }

    /**
     * Reassign pivot rows without creating duplicate relationships.
     */
    private function moveRelationships(string $table, string $relatedKey, int $sourceId, int $targetId): void
    {
        DB::table($table)
            ->where('facility_id', $sourceId)
            ->get()
            ->each(function (object $relationship) use ($table, $relatedKey, $targetId): void {
                $alreadyAssigned = DB::table($table)
                    ->where('facility_id', $targetId)
                    ->where($relatedKey, $relationship->{$relatedKey})
                    ->exists();

                if ($alreadyAssigned) {
                    DB::table($table)->where('id', $relationship->id)->delete();

                    return;
                }

                DB::table($table)
                    ->where('id', $relationship->id)
                    ->update(['facility_id' => $targetId]);
            });
    }

    /**
     * Convert known aliases and formatting differences to one comparison key.
     */
    private function normalizedName(string $name): string
    {
        $name = Str::lower(Str::squish($name));

        return match ($name) {
            'air conditioned' => 'air conditioning',
            default => $name,
        };
    }

    /**
     * Return the canonical name retained in the facilities table.
     */
    private function displayName(string $name): string
    {
        return match ($this->normalizedName($name)) {
            'air conditioning' => 'Air Conditioning',
            default => Str::squish($name),
        };
    }
};
