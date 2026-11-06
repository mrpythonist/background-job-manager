<?php

namespace App\Console\Commands;

use App\Services\JobDispatcher;
use Illuminate\Console\Command;

class DispatchSampleJobsCommand extends Command
{
    protected $signature = 'jobs:dispatch-sample {--count=5 : How many sample jobs to dispatch}';

    protected $description = 'Dispatch sample jobs across the default queues for demo purposes';

    protected array $samples = [
        ['queue' => 'emails', 'handler' => 'SendEmail', 'payload' => ['to' => 'demo@example.com']],
        ['queue' => 'reports', 'handler' => 'GenerateReport', 'payload' => ['report' => 'monthly-usage']],
        ['queue' => 'images', 'handler' => 'ResizeImage', 'payload' => ['path' => 'uploads/photo.jpg', 'width' => 800]],
        ['queue' => 'sync', 'handler' => 'SyncExternalData', 'payload' => ['source' => 'crm']],
    ];

    public function handle(JobDispatcher $dispatcher): int
    {
        $count = (int) $this->option('count');

        for ($i = 0; $i < $count; $i++) {
            $sample = $this->samples[$i % count($this->samples)];
            $sample['payload']['simulate'] = ['duration_ms' => 500 + ($i * 100)];
            $job = $dispatcher->dispatch($sample);
            $this->info("Dispatched job #{$job->id} ({$job->handler} on {$job->queue_name}).");
        }

        return self::SUCCESS;
    }
}
