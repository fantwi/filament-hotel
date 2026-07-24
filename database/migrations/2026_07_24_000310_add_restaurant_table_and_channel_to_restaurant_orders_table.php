<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurant_orders', function (Blueprint $table): void {
            $table->foreignId('restaurant_table_id')->nullable()->after('restaurant_reservation_id')
                ->constrained('restaurant_tables')->nullOnDelete();
            $table->string('ordering_channel')->default('web')->after('restaurant_table_id');
        });
    }

    public function down(): void
    {
        Schema::table('restaurant_orders', function (Blueprint $table): void {
            $table->dropForeign(['restaurant_table_id']);
            $table->dropColumn(['restaurant_table_id', 'ordering_channel']);
        });
    }
};
