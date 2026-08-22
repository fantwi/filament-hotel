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
        Schema::table('room_types', fn (Blueprint $table) => $table->json('gallery')->nullable()->after('image'));
    }

    /**
     * Reverts this database schema change.
     */
    public function down(): void
    {
        Schema::table('room_types', fn (Blueprint $table) => $table->dropColumn('gallery'));
    }
};
