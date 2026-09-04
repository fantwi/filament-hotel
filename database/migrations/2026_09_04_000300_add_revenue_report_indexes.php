<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add indexes for the period and status predicates used by revenue reporting.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->index(
                ['created_at', 'payment_status'],
                'payments_revenue_period_status_index',
            );
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->index(
                ['created_at', 'status', 'payment_status'],
                'bookings_revenue_period_status_index',
            );
        });

        Schema::table('conference_bookings', function (Blueprint $table): void {
            $table->index(
                ['created_at', 'status', 'payment_status'],
                'conference_bookings_revenue_period_status_index',
            );
        });

        Schema::table('restaurant_reservations', function (Blueprint $table): void {
            $table->index(
                ['created_at', 'status', 'payment_status'],
                'restaurant_reservations_revenue_period_status_index',
            );
        });

        Schema::table('restaurant_orders', function (Blueprint $table): void {
            $table->index(
                ['created_at', 'status', 'payment_status'],
                'restaurant_orders_revenue_period_status_index',
            );
        });
    }

    /**
     * Remove the revenue reporting indexes.
     */
    public function down(): void
    {
        Schema::table('restaurant_orders', function (Blueprint $table): void {
            $table->dropIndex('restaurant_orders_revenue_period_status_index');
        });

        Schema::table('restaurant_reservations', function (Blueprint $table): void {
            $table->dropIndex('restaurant_reservations_revenue_period_status_index');
        });

        Schema::table('conference_bookings', function (Blueprint $table): void {
            $table->dropIndex('conference_bookings_revenue_period_status_index');
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropIndex('bookings_revenue_period_status_index');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex('payments_revenue_period_status_index');
        });
    }
};
