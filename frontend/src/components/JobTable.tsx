import type { Job } from '../types';
import StatusBadge from './StatusBadge';

export function formatDuration(ms: number | null | undefined): string {
  if (ms === null || ms === undefined) return '-';
  if (ms < 1000) return `${ms} ms`;
  return `${(ms / 1000).toFixed(2)} s`;
}

export default function JobTable({
  jobs,
  onSelect,
  onRetry,
  onCancel,
}: {
  jobs: Job[];
  onSelect: (job: Job) => void;
  onRetry: (job: Job) => void;
  onCancel: (job: Job) => void;
}) {
  if (jobs.length === 0) {
    return <p className="py-8 text-center text-sm text-gray-500">No jobs match these filters.</p>;
  }
  return (
    <div className="overflow-x-auto">
      <table className="min-w-full divide-y divide-gray-200 text-sm">
        <thead className="bg-gray-50">
          <tr>
            <th className="px-4 py-2 text-left font-medium text-gray-500">ID</th>
            <th className="px-4 py-2 text-left font-medium text-gray-500">Handler</th>
            <th className="px-4 py-2 text-left font-medium text-gray-500">Queue</th>
            <th className="px-4 py-2 text-left font-medium text-gray-500">Status</th>
            <th className="px-4 py-2 text-left font-medium text-gray-500">Attempts</th>
            <th className="px-4 py-2 text-left font-medium text-gray-500">Duration</th>
            <th className="px-4 py-2 text-left font-medium text-gray-500">Created</th>
            <th className="px-4 py-2 text-left font-medium text-gray-500">Actions</th>
          </tr>
        </thead>
        <tbody className="divide-y divide-gray-200 bg-white">
          {jobs.map((job) => (
            <tr key={job.id} className="hover:bg-gray-50">
              <td className="px-4 py-2 text-gray-500">#{job.id}</td>
              <td className="px-4 py-2">
                <button className="font-medium text-indigo-600 hover:underline" onClick={() => onSelect(job)}>
                  {job.handler}
                </button>
              </td>
              <td className="px-4 py-2">{job.queue_name}</td>
              <td className="px-4 py-2"><StatusBadge status={job.status} /></td>
              <td className="px-4 py-2">{job.attempts}/{job.max_attempts}</td>
              <td className="px-4 py-2">{formatDuration(job.duration_ms)}</td>
              <td className="px-4 py-2 text-gray-500">{new Date(job.created_at).toLocaleString()}</td>
              <td className="px-4 py-2">
                <div className="flex gap-2">
                  {job.status === 'failed' && (
                    <button className="rounded bg-indigo-600 px-2 py-1 text-xs font-medium text-white hover:bg-indigo-700" onClick={() => onRetry(job)}>
                      Retry
                    </button>
                  )}
                  {(job.status === 'pending' || job.status === 'retrying') && (
                    <button className="rounded bg-gray-200 px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-300" onClick={() => onCancel(job)}>
                      Cancel
                    </button>
                  )}
                </div>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
