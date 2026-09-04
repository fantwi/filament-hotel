<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add indexes for the date and status predicates used by occupancy reporting.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['check_in', 'status'], 'bookings_check_in_status_index');
            $table->index(['check_out', 'status'], 'bookings_check_out_status_index');
        });

        Schema::table('conference_bookings', function (Blueprint $table) {
            $table->index(['booking_date', 'status'], 'conference_bookings_booking_date_status_index');
        });

        Schema::table('restaurant_reservations', function (Blueprint $table) {
            $table->index(['reservation_date', 'status'], 'restaurant_reservations_reservation_date_status_index');
        });
    }

    /**
     * Remove the occupancy reporting indexes.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_check_in_status_index');
            $table->dropIndex('bookings_check_out_status_index');
        });

        Schema::table('conference_bookings', function (Blueprint $table) {
            $table->dropIndex('conference_bookings_booking_date_status_index');
        });

        Schema::table('restaurant_reservations', function (Blueprint $table) {
            $table->dropIndex('restaurant_reservations_reservation_date_status_index');
        });
    }
};
