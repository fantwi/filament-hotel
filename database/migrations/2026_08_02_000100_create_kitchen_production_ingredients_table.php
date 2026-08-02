<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kitchen_production_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kitchen_production_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity_used', 12, 3);
            $table->string('unit', 30)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['kitchen_production_id', 'ingredient_id'], 'prod_ingredient_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kitchen_production_ingredients');
    }
};
