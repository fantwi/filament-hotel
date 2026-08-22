<?php

namespace App\Console\Commands;

use App\Models\RestaurantTable;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Provides the generate restaurant table qr tokens console operation.
 */
class GenerateRestaurantTableQrTokens extends Command
{
    protected $signature = 'restaurant:generate-table-qr-tokens';

    protected $description = 'Generate QR ordering tokens for restaurant tables';

    /**
     * Processes the current job, command, listener, or middleware request.
     */
    public function handle(): int
    {
        $tables = RestaurantTable::query()->whereNull('qr_token')->get();

        if ($tables->isEmpty()) {
            $this->info('All restaurant tables already have QR tokens.');

            return self::SUCCESS;
        }

        foreach ($tables as $table) {
            $table->update(['qr_token' => Str::random(48)]);
            $this->line("Generated token for table {$table->table_number}.");
        }

        $this->info("{$tables->count()} QR tokens generated.");

        return self::SUCCESS;
    }
}
