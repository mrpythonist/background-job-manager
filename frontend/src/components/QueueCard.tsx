import type { Queue } from '../types';

export default function QueueCard({
  queue,
  onPause,
  onResume,
  onDelete,
}: {
  queue: Queue;
  onPause: (q: Queue) => void;
  onResume: (q: Queue) => void;
  onDelete: (q: Queue) => void;
}) {
  return (
    <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
      <div className="flex items-center justify-between">
        <h3 className="text-lg font-semibold text-gray-900">{queue.name}</h3>
        {queue.is_paused ? (
          <span className="rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800">Paused</span>
        ) : (
          <span className="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">Active</span>
        )}
      </div>
      <p className="mt-1 text-sm text-gray-500">
        Priority {queue.priority} · Concurrency {queue.concurrency}
      </p>
      <div className="mt-3 grid grid-cols-4 gap-2 text-center text-sm">
        <div><p className="font-semibold">{queue.pending_count ?? 0}</p><p className="text-xs text-gray-400">Pending</p></div>
        <div><p className="font-semibold">{queue.processing_count ?? 0}</p><p className="text-xs text-gray-400">Running</p></div>
        <div><p className="font-semibold">{queue.completed_count ?? 0}</p><p className="text-xs text-gray-400">Done</p></div>
        <div><p className="font-semibold text-red-600">{queue.failed_count ?? 0}</p><p className="text-xs text-gray-400">Failed</p></div>
      </div>
      <div className="mt-4 flex gap-2">
        {queue.is_paused ? (
          <button className="rounded bg-green-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-700" onClick={() => onResume(queue)}>
            Resume
          </button>
        ) : (
          <button className="rounded bg-yellow-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-yellow-600" onClick={() => onPause(queue)}>
            Pause
          </button>
        )}
        <button
          className="rounded bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-100 disabled:opacity-50"
          disabled={(queue.jobs_count ?? 0) > 0}
          title={(queue.jobs_count ?? 0) > 0 ? 'Queues with jobs cannot be deleted' : 'Delete this queue'}
          onClick={() => onDelete(queue)}
        >
          Delete
        </button>
      </div>
    </div>
  );
}
