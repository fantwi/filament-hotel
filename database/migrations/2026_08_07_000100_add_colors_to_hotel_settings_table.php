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
        Schema::table('hotel_settings', function (Blueprint $table): void {
            $table->string('primary_color', 7)->default('#2563EB')->after('logo');
            $table->string('secondary_color', 7)->default('#0EA5E9')->after('primary_color');
            $table->string('footer_color', 7)->default('#161B48')->after('secondary_color');
        });
    }

    /**
     * Reverts this database schema change.
     */
    public function down(): void
    {
        Schema::table('hotel_settings', function (Blueprint $table): void {
            $table->dropColumn(['primary_color', 'secondary_color', 'footer_color']);
        });
    }
};
