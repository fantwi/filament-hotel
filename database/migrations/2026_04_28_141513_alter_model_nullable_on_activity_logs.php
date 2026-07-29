<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        //
        Schema::table('activity_logs', function (Blueprint $table) {

            $table->string('model')
                ->nullable()
                ->change();

        });

        Schema::table('activity_logs', function (Blueprint $table) {

            $table->string('record_id')
                ->nullable()
                ->change();

        });

        Schema::table('activity_logs', function (Blueprint $table) {

            $table->string('old_values')
                ->nullable()
                ->change();

        });

        Schema::table('activity_logs', function (Blueprint $table) {

            $table->string('new_values')
                ->nullable()
                ->change();

        });

        Schema::table('activity_logs', function (Blueprint $table) {

            $table->string('ip_address')
                ->nullable()
                ->change();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('activity_logs', function (Blueprint $table) {

            $table->string('model')
                ->nullable(false)
                ->change();

        });

        Schema::table('activity_logs', function (Blueprint $table) {

            $table->string('record_id')
                ->nullable(false)
                ->change();

        });

        Schema::table('activity_logs', function (Blueprint $table) {

            $table->string('old_values')
                ->nullable(false)
                ->change();

        });

        Schema::table('activity_logs', function (Blueprint $table) {

            $table->string('new_values')
                ->nullable(false)
                ->change();

        });

        Schema::table('activity_logs', function (Blueprint $table) {

            $table->string('ip_address')
                ->nullable(false)
                ->change();

        });
    }
};
