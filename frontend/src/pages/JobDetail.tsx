import { useCallback, useEffect, useState } from 'react';
import { api } from '../api';
import type { Job } from '../types';
import StatusBadge from '../components/StatusBadge';
import { formatDuration } from '../components/JobTable';

export default function JobDetail({ jobId, onBack }: { jobId: number; onBack: () => void }) {
  const [job, setJob] = useState<Job | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);

  const load = useCallback(async () => {
    try {
      setJob(await api.getJob(jobId));
      setError(null);
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Failed to load job.');
    }
  }, [jobId]);

  useEffect(() => {
    load();
    const timer = setInterval(load, 5000);
    return () => clearInterval(timer);
  }, [load]);

  if (error) {
    return (
      <div className="space-y-4">
        <button className="text-sm font-medium text-indigo-600 hover:underline" onClick={onBack}>Back to jobs</button>
        <div className="rounded-md bg-red-50 p-4 text-sm text-red-700">{error}</div>
      </div>
    );
  }
  if (!job) return <p className="text-sm text-gray-500">Loading job...</p>;

  const attempts = job.job_attempts ?? [];

  return (
    <div className="space-y-6">
      <button className="text-sm font-medium text-indigo-600 hover:underline" onClick={onBack}>Back to jobs</button>
      {message && <div className="rounded-md bg-green-50 p-4 text-sm text-green-700">{message}</div>}

      <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <div className="flex flex-wrap items-center justify-between gap-2">
          <h2 className="text-xl font-semibold text-gray-900">Job #{job.id} · {job.handler}</h2>
          <StatusBadge status={job.status} />
        </div>
        <dl className="mt-4 grid gap-3 text-sm md:grid-cols-2">
          <div><dt className="text-gray-500">Queue</dt><dd className="font-medium">{job.queue_name}</dd></div>
          <div><dt className="text-gray-500">Priority</dt><dd className="font-medium">{job.priority}</dd></div>
          <div><dt className="text-gray-500">Attempts</dt><dd className="font-medium">{job.attempts} / {job.max_attempts}</dd></div>
          <div><dt className="text-gray-500">Duration</dt><dd className="font-medium">{formatDuration(job.duration_ms)}</dd></div>
          <div><dt className="text-gray-500">Started</dt><dd className="font-medium">{job.started_at ? new Date(job.started_at).toLocaleString() : '-'}</dd></div>
          <div><dt className="text-gray-500">Finished</dt><dd className="font-medium">{job.finished_at ? new Date(job.finished_at).toLocaleString() : '-'}</dd></div>
          <div><dt className="text-gray-500">Created</dt><dd className="font-medium">{new Date(job.created_at).toLocaleString()}</dd></div>
          <div><dt className="text-gray-500">Delay</dt><dd className="font-medium">{job.delay_seconds}s</dd></div>
        </dl>
        {job.last_error && (
          <p className="mt-4 rounded-md bg-red-50 p-3 text-sm text-red-700">Last error: {job.last_error}</p>
        )}
        <div className="mt-4 flex gap-2">
          {job.status === 'failed' && (
            <button
              className="rounded bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700"
              onClick={async () => { await api.retryJob(job.id); setMessage(`Job #${job.id} queued for retry.`); await load(); }}
            >
              Retry
            </button>
          )}
          {(job.status === 'pending' || job.status === 'retrying') && (
            <button
              className="rounded bg-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-300"
              onClick={async () => { await api.cancelJob(job.id); setMessage(`Job #${job.id} cancelled.`); await load(); }}
            >
              Cancel
            </button>
          )}
        </div>
      </div>

      <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <h3 className="font-semibold text-gray-900">Payload</h3>
        <pre className="mt-2 overflow-x-auto rounded bg-gray-50 p-3 text-xs">{JSON.stringify(job.payload, null, 2)}</pre>
      </div>

      <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <h3 className="font-semibold text-gray-900">Attempts</h3>
        {attempts.length === 0 ? (
          <p className="mt-2 text-sm text-gray-500">No attempts recorded yet.</p>
        ) : (
          <ol className="mt-3 space-y-3">
            {attempts.map((a) => (
              <li key={a.id} className="border-l-2 border-indigo-200 pl-3 text-sm">
                <p className="font-medium">Attempt {a.attempt_number} · {a.status}{a.worker_id ? ` · Worker #${a.worker_id}` : ''}</p>
                <p className="text-gray-500">
                  {a.started_at ? new Date(a.started_at).toLocaleString() : '-'}
                  {a.finished_at ? ` to ${new Date(a.finished_at).toLocaleString()}` : ''}
                  {' · '}{formatDuration(a.duration_ms)}
                </p>
                {a.error && <p className="text-red-700">{a.error}</p>}
              </li>
            ))}
          </ol>
        )}
      </div>
    </div>
  );
}
