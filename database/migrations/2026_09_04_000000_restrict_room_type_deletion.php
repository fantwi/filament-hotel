<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Prevent deleting a room type from cascading through rooms and booking history.
     */
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table): void {
            $table->dropForeign(['room_type_id']);
            $table->foreign('room_type_id')
                ->references('id')
                ->on('room_types')
                ->restrictOnDelete();
        });
    }

    /**
     * Restore the original cascading relationship.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table): void {
            $table->dropForeign(['room_type_id']);
            $table->foreign('room_type_id')
                ->references('id')
                ->on('room_types')
                ->cascadeOnDelete();
        });
    }
};
