<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('restaurant_reservations', 'transaction_reference')) {
            Schema::table('restaurant_reservations', function (Blueprint $table): void {
                $table->string('transaction_reference')->nullable()->unique();
            });
        }

        Schema::table('restaurant_reservations', function (Blueprint $table): void {
            $table->string('access_token', 64)->nullable();
        });

        DB::table('restaurant_reservations')
            ->orderBy('id')
            ->each(function (object $reservation): void {
                DB::table('restaurant_reservations')
                    ->where('id', $reservation->id)
                    ->update(['access_token' => Str::random(64)]);
            });

        Schema::table('restaurant_reservations', function (Blueprint $table): void {
            $table->unique('access_token');
        });
    }

    public function down(): void
    {
        Schema::table('restaurant_reservations', function (Blueprint $table): void {
            $table->dropUnique(['access_token']);
            $table->dropColumn('access_token');
        });
    }
};
