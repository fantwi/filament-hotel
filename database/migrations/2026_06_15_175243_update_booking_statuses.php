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
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("
            ALTER TABLE bookings
            MODIFY status
            ENUM(
                'pending',
                'confirmed',
                'checked_in',
                'checked_out',
                'cancelled',
                'expired',
                'no_show'
            )
            DEFAULT 'pending'
        ");
        DB::statement("
            ALTER TABLE bookings
            MODIFY payment_status
            ENUM(
                'pending',
                'paid',
                'refunded',
                'expired'
            )
            DEFAULT 'pending'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("
            ALTER TABLE bookings
            MODIFY status
            ENUM(
                'pending',
                'confirmed',
                'checked_in',
                'checked_out',
                'cancelled',
                'expired'
            )
            DEFAULT 'pending'
        ");
        DB::statement("
            ALTER TABLE bookings
            MODIFY payment_status
            ENUM(
                'pending',
                'paid',
                'refunded',
                'expired',
                'unpaid',
                'failed',
                'partially_paid'
            )
            DEFAULT 'pending'
        ");
    }
};
