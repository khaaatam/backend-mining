<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Creates the next month's partition for gps_pings.
 * Should be scheduled to run on the 1st of each month.
 *
 * Schedule in routes/console.php or GpsServiceProvider:
 *   Schedule::command('gps:create-partition')->monthlyOn(1, '00:05');
 */
class CreateGpsPartitionCommand extends Command
{
    protected $signature   = 'gps:create-partition {--month= : YYYY-MM, defaults to next month}';
    protected $description = 'Create the next monthly partition for the gps_pings table';

    public function handle(): int
    {
        $target = $this->option('month')
            ? \Carbon\Carbon::createFromFormat('Y-m', $this->option('month'))->startOfMonth()
            : now()->addMonth()->startOfMonth();

        $name  = 'gps_pings_' . $target->format('Y_m');
        $start = $target->toDateString();
        $end   = $target->copy()->addMonth()->startOfMonth()->toDateString();

        // Check if partition already exists
        $exists = DB::selectOne("
            SELECT 1 FROM pg_tables WHERE tablename = ?
        ", [$name]);

        if ($exists) {
            $this->info("Partition {$name} already exists — nothing to do.");
            return self::SUCCESS;
        }

        DB::statement("
            CREATE TABLE {$name}
            PARTITION OF gps_pings
            FOR VALUES FROM ('{$start}') TO ('{$end}')
        ");

        $this->info("Created partition: {$name} ({$start} → {$end})");

        return self::SUCCESS;
    }
}
