<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurant_orders', function (Blueprint $table): void {
            $table->text('kitchen_notes')->nullable()->after('notes');
            $table->timestamp('confirmed_at')->nullable()->after('paid_at');
            $table->timestamp('preparing_at')->nullable()->after('confirmed_at');
            $table->timestamp('ready_at')->nullable()->after('preparing_at');
            $table->timestamp('served_at')->nullable()->after('ready_at');
            $table->timestamp('cancelled_at')->nullable()->after('served_at');
            $table->foreignId('prepared_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
            $table->foreignId('served_by')->nullable()->after('prepared_by')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('restaurant_orders', function (Blueprint $table): void {
            $table->dropForeign(['prepared_by']);
            $table->dropForeign(['served_by']);
            $table->dropColumn([
                'kitchen_notes', 'confirmed_at', 'preparing_at', 'ready_at',
                'served_at', 'cancelled_at', 'prepared_by', 'served_by',
            ]);
        });
    }
};
