<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurant_orders', function (Blueprint $table) {
            $table->string('customer_email')->nullable()->after('order_number');
            $table->string('transaction_reference')->nullable()->unique()->after('customer_email');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('restaurant_order_id')->nullable()->after('restaurant_reservation_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('restaurant_order_id');
        });

        Schema::table('restaurant_orders', function (Blueprint $table) {
            $table->dropUnique(['transaction_reference']);
            $table->dropColumn(['customer_email', 'transaction_reference']);
        });
    }
};
