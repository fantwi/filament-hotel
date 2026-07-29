<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'restaurants',
        'menu_categories',
        'menu_items',
        'room_types',
        'conference_rooms',
        'facilities',
        'conference_facilities',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                // Keep existing records available to guests after the upgrade.
                $table->boolean('is_published')->default(true)->index()->after('id');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->after('is_published');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->dropConstrainedForeignId('created_by');
                $table->dropIndex($tableName.'_is_published_index');
                $table->dropColumn('is_published');
            });
        }
    }
};
