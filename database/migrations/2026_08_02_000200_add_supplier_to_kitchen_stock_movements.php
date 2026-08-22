<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Applies this database schema change.
     */
    public function up(): void
    {
        Schema::table('kitchen_stock_movements', function (Blueprint $table) {
            $table->string('supplier_name')->nullable()->after('reference_number');
        });
    }

    /**
     * Reverts this database schema change.
     */
    public function down(): void
    {
        Schema::table('kitchen_stock_movements', function (Blueprint $table) {
            $table->dropColumn('supplier_name');
        });
    }
};
