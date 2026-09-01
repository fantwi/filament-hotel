<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove the obsolete work-shift field from staff accounts.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'shift')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('shift');
        });
    }

    /**
     * Restore the former work-shift field when this migration is rolled back.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'shift')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->string('shift')->default('off_duty');
        });
    }
};
