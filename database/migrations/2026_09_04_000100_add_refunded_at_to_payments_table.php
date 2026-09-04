<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds an immutable event timestamp for date-accurate refund reporting.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->timestamp('refunded_at')->nullable()->index();
        });

        DB::table('payments')
            ->whereIn('payment_status', ['refunded', 'refund'])
            ->whereNull('refunded_at')
            ->update(['refunded_at' => DB::raw('updated_at')]);
    }

    /**
     * Removes the dedicated refund event timestamp.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex(['refunded_at']);
            $table->dropColumn('refunded_at');
        });
    }
};
