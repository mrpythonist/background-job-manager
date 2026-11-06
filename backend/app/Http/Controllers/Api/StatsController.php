<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Services\QueueStatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function __construct(protected QueueStatsService $stats) {}

    /** Plain-array projection of a job row, the shape QueueStatsService expects. */
    protected function rows(): array
    {
        return Job::select(['status', 'queue_name', 'duration_ms', 'finished_at', 'created_at'])
            ->get()
            ->map(fn (Job $job) => [
                'status' => $job->status,
                'queue_name' => $job->queue_name,
                'duration_ms' => $job->duration_ms,
                'finished_at' => $job->finished_at?->timestamp,
                'created_at' => $job->created_at?->timestamp,
            ])->all();
    }

    public function overview(): JsonResponse
    {
        return response()->json($this->stats->overview($this->rows()));
    }

    public function queues(): JsonResponse
    {
        return response()->json($this->stats->countsByQueue($this->rows()));
    }

    public function timeseries(Request $request): JsonResponse
    {
        $hours = min(max((int) $request->query('hours', 24), 1), 168);

        return response()->json($this->stats->timeseries($this->rows(), $hours));
    }
}
