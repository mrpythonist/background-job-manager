<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJobRequest;
use App\Models\Job;
use App\Services\JobDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function __construct(protected JobDispatcher $dispatcher) {}

    public function index(Request $request): JsonResponse
    {
        $query = Job::with(['queue', 'jobAttempts'])->orderByDesc('created_at');

        if ($request->filled('queue')) {
            $query->forQueue($request->string('queue')->toString());
        }
        if ($request->filled('status') && in_array($request->string('status')->toString(), Job::STATUSES, true)) {
            $query->byStatus($request->string('status')->toString());
        }
        if ($request->filled('search')) {
            $search = '%' . $request->string('search')->toString() . '%';
            $query->where(function ($q) use ($search) {
                $q->where('handler', 'like', $search)
                    ->orWhere('queue_name', 'like', $search);
            });
        }

        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);

        return response()->json($query->paginate($perPage));
    }

    public function store(StoreJobRequest $request): JsonResponse
    {
        $job = $this->dispatcher->dispatch($request->validated());

        return response()->json($job->load('queue'), 201);
    }

    public function show(Job $job): JsonResponse
    {
        return response()->json($job->load(['queue', 'jobAttempts.worker']));
    }

    public function retry(Job $job): JsonResponse
    {
        if (! $job->isRetryable()) {
            return response()->json([
                'message' => 'Only failed jobs with attempts remaining can be retried.',
            ], 422);
        }

        $retried = $this->dispatcher->retry($job);

        return response()->json($retried->fresh()->load('queue'));
    }

    public function cancel(Job $job): JsonResponse
    {
        if (! $this->dispatcher->cancel($job)) {
            return response()->json([
                'message' => 'Only pending or retrying jobs can be cancelled.',
            ], 422);
        }

        return response()->json($job->fresh()->load('queue'));
    }
}
