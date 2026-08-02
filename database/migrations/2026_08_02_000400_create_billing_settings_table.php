<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration { public function up(): void { Schema::create('billing_settings', function (Blueprint $table) { $table->id(); $table->decimal('vat_rate', 5, 2)->default(12.5); $table->decimal('nhil_rate', 5, 2)->default(2.5); $table->decimal('service_charge_rate', 5, 2)->default(2); $table->timestamps(); }); } public function down(): void { Schema::dropIfExists('billing_settings'); } };
