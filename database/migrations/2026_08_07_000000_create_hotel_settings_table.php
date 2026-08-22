<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Applies this database schema change.
     */
    public function up(): void
    {
        Schema::create('hotel_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('hotel_name');
            $table->string('logo')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverts this database schema change.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotel_settings');
    }
};
