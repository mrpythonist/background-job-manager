<?php

namespace App\Console\Commands;

use App\Services\WorkerRegistry;
use Illuminate\Console\Command;

class PruneStaleWorkersCommand extends Command
{
    protected $signature = 'workers:prune-stale {--stale-after=60 : Seconds without a heartbeat before a worker is stale}';

    protected $description = 'Mark workers with no recent heartbeat as offline';

    public function handle(WorkerRegistry $registry): int
    {
        $count = $registry->pruneStale((int) $this->option('stale-after'));

        $this->info("Marked {$count} stale worker(s) offline.");

        return self::SUCCESS;
    }
}
