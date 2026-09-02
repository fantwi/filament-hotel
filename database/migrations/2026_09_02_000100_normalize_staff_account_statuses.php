<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->whereIn('status', ['online', 'offline'])->update(['status' => 'active']);
        DB::table('users')->where(function ($query): void {
            $query->whereNull('status')->orWhere('status', '');
        })->update(['status' => 'suspended']);
        DB::table('users')->whereNotIn('status', ['active', 'on_leave', 'suspended'])->update(['status' => 'suspended']);

        Schema::table('users', function (Blueprint $table): void {
            $table->string('status')->default('active')->change();
        });
    }

    public function down(): void
    {
        DB::table('users')->where('status', 'active')->update(['status' => 'offline']);

        Schema::table('users', function (Blueprint $table): void {
            $table->string('status')->default('online')->change();
        });
    }
};
