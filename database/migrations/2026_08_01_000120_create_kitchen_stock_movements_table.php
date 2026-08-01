<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kitchen_stock_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ingredient_id')->constrained()->restrictOnDelete();
            $table->string('type', 40);
            $table->string('direction', 10);
            $table->decimal('quantity', 14, 3);
            $table->decimal('balance_before', 14, 3);
            $table->decimal('balance_after', 14, 3);
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->decimal('total_cost', 14, 2)->nullable();
            $table->string('reference_number')->nullable();
            $table->nullableMorphs('reference');
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('occurred_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['type', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kitchen_stock_movements');
    }
};
