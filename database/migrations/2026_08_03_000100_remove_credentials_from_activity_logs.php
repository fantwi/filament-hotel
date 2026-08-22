<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Applies this database schema change.
     */
    public function up(): void
    {
        foreach (array_unique([
            config('activitylog.table_name'),
            'activity_log',
            'activity_logs',
        ]) as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'properties')) {
                continue;
            }

            DB::table($table)->orderBy('id')->eachById(function (object $activity) use ($table): void {
                $properties = json_decode((string) $activity->properties, true);

                if (! is_array($properties)) {
                    return;
                }

                $changed = false;

                foreach (['attributes', 'old'] as $section) {
                    if (! isset($properties[$section]) || ! is_array($properties[$section])) {
                        continue;
                    }

                    foreach (['password', 'remember_token'] as $attribute) {
                        if (array_key_exists($attribute, $properties[$section])) {
                            unset($properties[$section][$attribute]);
                            $changed = true;
                        }
                    }
                }

                if ($changed) {
                    DB::table($table)
                        ->where('id', $activity->id)
                        ->update(['properties' => json_encode($properties, JSON_THROW_ON_ERROR)]);
                }
            });
        }
    }

    /**
     * Reverts this database schema change.
     */
    public function down(): void
    {
        // Removed credentials cannot and must not be restored.
    }
};
