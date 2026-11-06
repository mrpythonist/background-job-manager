<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Queue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QueueController extends Controller
{
    public function index(): JsonResponse
    {
        $queues = Queue::withCount([
            'jobs',
            'jobs as pending_count' => fn ($q) => $q->where('status', 'pending'),
            'jobs as processing_count' => fn ($q) => $q->where('status', 'processing'),
            'jobs as completed_count' => fn ($q) => $q->where('status', 'completed'),
            'jobs as failed_count' => fn ($q) => $q->where('status', 'failed'),
        ])->orderBy('priority')->orderBy('name')->get();

        return response()->json($queues);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'alpha_dash', 'unique:queues,name'],
            'priority' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'concurrency' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $queue = Queue::create([
            'name' => $data['name'],
            'priority' => $data['priority'] ?? 5,
            'concurrency' => $data['concurrency'] ?? 1,
        ]);

        return response()->json($queue, 201);
    }

    public function show(Queue $queue): JsonResponse
    {
        return response()->json($queue->load(['jobs' => fn ($q) => $q->latest()->limit(20)]));
    }

    public function update(Request $request, Queue $queue): JsonResponse
    {
        $data = $request->validate([
            'priority' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'concurrency' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'is_paused' => ['sometimes', 'boolean'],
        ]);

        $queue->update($data);

        return response()->json($queue->fresh());
    }

    public function pause(Queue $queue): JsonResponse
    {
        $queue->update(['is_paused' => true]);
        return response()->json($queue->fresh());
    }

    public function resume(Queue $queue): JsonResponse
    {
        $queue->update(['is_paused' => false]);
        return response()->json($queue->fresh());
    }

    public function destroy(Queue $queue): JsonResponse
    {
        if ($queue->jobs()->exists()) {
            return response()->json([
                'message' => 'Cannot delete a queue that still has jobs. Cancel or delete the jobs first.',
            ], 422);
        }

        $queue->delete();

        return response()->json(null, 204);
    }
}
