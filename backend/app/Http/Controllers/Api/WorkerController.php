<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Worker;
use App\Services\WorkerRegistry;
use Illuminate\Http\JsonResponse;

class WorkerController extends Controller
{
    public function __construct(protected WorkerRegistry $registry) {}

    public function index(): JsonResponse
    {
        return response()->json($this->registry->statusList());
    }

    public function show(Worker $worker): JsonResponse
    {
        $worker->load('currentJob');

        return response()->json([
            'id' => $worker->id,
            'name' => $worker->name,
            'hostname' => $worker->hostname,
            'status' => $worker->effectiveStatus(),
            'current_job' => $worker->currentJob,
            'queues' => $worker->queues ?? [],
            'processed_jobs' => $worker->processed_jobs,
            'failed_jobs' => $worker->failed_jobs,
            'last_heartbeat_at' => $worker->last_heartbeat_at?->toIso8601String(),
            'started_at' => $worker->started_at?->toIso8601String(),
        ]);
    }
}
