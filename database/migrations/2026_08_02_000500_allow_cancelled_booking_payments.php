<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') return;
        DB::statement("ALTER TABLE bookings MODIFY payment_status ENUM('pending','paid','refunded','expired','cancelled','unpaid','failed','partially_paid') DEFAULT 'pending'");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') return;
        DB::statement("ALTER TABLE bookings MODIFY payment_status ENUM('pending','paid','refunded','expired') DEFAULT 'pending'");
    }
};
