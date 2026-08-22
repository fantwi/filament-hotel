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
        Schema::table('restaurant_tables', function (Blueprint $table): void {
            $table->decimal('reservation_fee', 12, 2)->default(0)->after('capacity');
        });
    }

    /**
     * Reverts this database schema change.
     */
    public function down(): void
    {
        Schema::table('restaurant_tables', function (Blueprint $table): void {
            $table->dropColumn('reservation_fee');
        });
    }
};
