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
        Schema::create('restaurant_reservations', function (Blueprint $table) {

            $table->id();

            $table->foreignId('restaurant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('restaurant_table_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('guest_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('guest_name');

            $table->string('guest_email');

            $table->string('guest_phone');

            $table->date('reservation_date');

            $table->time('reservation_time');

            $table->unsignedInteger('number_of_guests');

            $table->decimal('reservation_fee', 10, 2)
                ->default(0);

            $table->enum('status', [
                'pending',
                'confirmed',
                'checked_in',
                'completed',
                'cancelled',
                'no_show',
            ])->default('pending');

            $table->enum('payment_status', [
                'pending',
                'partial',
                'completed',
                'cancelled',
                'refunded',
            ])->default('pending');

            $table->timestamp('hold_until')->nullable();

            $table->enum('hold_status', [
                'held',
                'confirmed',
                'expired',
            ])->default('held');

            $table->text('special_requests')->nullable();

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restaurant_reservations');
    }
};
