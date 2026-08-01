<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('sku')->nullable()->unique();
            $table->string('category')->nullable();
            $table->string('unit', 30)->default('unit');
            $table->decimal('current_stock', 14, 3)->default(0);
            $table->decimal('reorder_level', 14, 3)->default(0);
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['restaurant_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredients');
    }
};
