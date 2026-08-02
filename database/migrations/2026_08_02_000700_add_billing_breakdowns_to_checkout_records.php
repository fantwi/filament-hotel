<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('restaurant_orders', function (Blueprint $table): void {$table->decimal('discount',12,2)->default(0)->after('subtotal');$table->decimal('vat',12,2)->default(0)->after('discount');$table->decimal('nhil',12,2)->default(0)->after('vat');});
  Schema::table('conference_bookings', function (Blueprint $table): void {$table->decimal('subtotal',12,2)->nullable()->after('attendees');$table->decimal('discount',12,2)->default(0)->after('subtotal');$table->decimal('vat',12,2)->default(0)->after('discount');$table->decimal('nhil',12,2)->default(0)->after('vat');$table->decimal('service_charge',12,2)->default(0)->after('nhil');$table->string('promotion_code')->nullable()->after('service_charge');});
  Schema::table('restaurant_reservations', function (Blueprint $table): void {$table->decimal('subtotal',12,2)->nullable()->after('reservation_fee');$table->decimal('discount',12,2)->default(0)->after('subtotal');$table->decimal('vat',12,2)->default(0)->after('discount');$table->decimal('nhil',12,2)->default(0)->after('vat');$table->decimal('service_charge',12,2)->default(0)->after('nhil');$table->string('promotion_code')->nullable()->after('service_charge');});
 }
 public function down(): void {
  Schema::table('restaurant_orders', function (Blueprint $table): void {$table->dropColumn(['discount','vat','nhil']);});
  Schema::table('conference_bookings', function (Blueprint $table): void {$table->dropColumn(['subtotal','discount','vat','nhil','service_charge','promotion_code']);});
  Schema::table('restaurant_reservations', function (Blueprint $table): void {$table->dropColumn(['subtotal','discount','vat','nhil','service_charge','promotion_code']);});
 }
};