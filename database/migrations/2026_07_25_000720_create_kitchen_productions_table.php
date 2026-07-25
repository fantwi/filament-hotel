<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('kitchen_productions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('menu_item_id')->constrained()->cascadeOnDelete();
            $table->string('batch_reference', 50)->unique();
            $table->date('production_date');
            $table->decimal('quantity_produced', 12, 3);
            $table->decimal('quantity_wasted', 12, 3)->default(0);
            $table->foreignId('produced_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['menu_item_id', 'production_date']);
        });
    }
    public function down(): void { Schema::dropIfExists('kitchen_productions'); }
};
