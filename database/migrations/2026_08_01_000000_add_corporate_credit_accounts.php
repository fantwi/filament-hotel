<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('corporate_organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->decimal('credit_limit', 12, 2)->nullable();
            $table->unsignedSmallInteger('payment_terms_days')->default(30);
            $table->boolean('is_credit_enabled')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('corporate_organization_id')->nullable()->after('id_number')
                ->constrained()->nullOnDelete();
        });

        foreach (['bookings', 'conference_bookings', 'restaurant_reservations', 'restaurant_orders'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('corporate_organization_id')->nullable()
                    ->constrained()->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['bookings', 'conference_bookings', 'restaurant_reservations', 'restaurant_orders'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('corporate_organization_id');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('corporate_organization_id');
        });

        Schema::dropIfExists('corporate_organizations');
    }
};
