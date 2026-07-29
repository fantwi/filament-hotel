<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('conference_facility_conference_room', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('conference_room_id');

            $table->unsignedBigInteger('conference_facility_id');

            $table->timestamps();

            // Short FK names
            $table->foreign('conference_room_id', 'conf_room_fk')
                ->references('id')
                ->on('conference_rooms')
                ->cascadeOnDelete();

            $table->foreign('conference_facility_id', 'conf_facility_fk')
                ->references('id')
                ->on('conference_facilities')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conference_facility_conference_room');
    }
};
