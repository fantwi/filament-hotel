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
        Schema::table('payments', function (Blueprint $table): void {
            $table->enum('method', ['cash', 'momo', 'card', 'paystack', 'corporate_account', 'bank_transfer'])->change();
        });
    }

    /**
     * Reverts this database schema change.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->enum('method', ['cash', 'momo', 'card', 'paystack', 'corporate_account'])->change();
        });
    }
};
