import type { Worker } from '../types';

const COLORS: Record<string, string> = {
  idle: 'bg-green-100 text-green-800',
  busy: 'bg-blue-100 text-blue-800',
  stale: 'bg-yellow-100 text-yellow-800',
  offline: 'bg-gray-100 text-gray-800',
};

export default function WorkerList({ workers }: { workers: Worker[] }) {
  if (workers.length === 0) {
    return <p className="py-8 text-center text-sm text-gray-500">No workers registered.</p>;
  }
  return (
    <ul className="divide-y divide-gray-200">
      {workers.map((w) => (
        <li key={w.id} className="flex flex-wrap items-center justify-between gap-2 py-3">
          <div>
            <p className="font-medium text-gray-900">{w.name}</p>
            <p className="text-xs text-gray-500">
              {w.hostname ?? 'unknown host'} · Queues: {w.queues.length > 0 ? w.queues.join(', ') : 'all'}
              {w.current_job_id ? ` · Running job #${w.current_job_id}` : ''}
            </p>
            <p className="text-xs text-gray-400">
              Last heartbeat: {w.last_heartbeat_at ? new Date(w.last_heartbeat_at).toLocaleString() : 'never'}
            </p>
          </div>
          <div className="flex items-center gap-3 text-sm">
            <span className="text-gray-600">{w.processed_jobs} done · {w.failed_jobs} failed</span>
            <span className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${COLORS[w.status] ?? COLORS.offline}`}>
              {w.status}
            </span>
          </div>
        </li>
      ))}
    </ul>
  );
}
