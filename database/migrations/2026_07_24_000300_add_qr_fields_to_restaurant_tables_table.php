<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurant_tables', function (Blueprint $table): void {
            $table->string('qr_token', 64)->nullable()->unique()->after('qr_code');
            $table->boolean('qr_ordering_enabled')->default(true)->after('qr_token');
        });
    }

    public function down(): void
    {
        Schema::table('restaurant_tables', function (Blueprint $table): void {
            $table->dropUnique(['qr_token']);
            $table->dropColumn(['qr_token', 'qr_ordering_enabled']);
        });
    }
};
