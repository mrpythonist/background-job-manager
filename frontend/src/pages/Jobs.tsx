import { useCallback, useEffect, useState } from 'react';
import { api } from '../api';
import type { Job, JobStatus, Queue } from '../types';
import { HANDLERS, STATUS_LABELS } from '../types';
import JobTable from '../components/JobTable';

const STATUSES = Object.keys(STATUS_LABELS) as JobStatus[];

export default function Jobs({ onSelectJob }: { onSelectJob: (job: Job) => void }) {
  const [jobs, setJobs] = useState<Job[]>([]);
  const [queues, setQueues] = useState<Queue[]>([]);
  const [queueFilter, setQueueFilter] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);

  const [formQueue, setFormQueue] = useState('default');
  const [formHandler, setFormHandler] = useState<string>('SendEmail');
  const [formPayload, setFormPayload] = useState('{\n  "to": "user@example.com",\n  "simulate": { "duration_ms": 800, "should_fail": false }\n}');
  const [formPriority, setFormPriority] = useState(5);
  const [formAttempts, setFormAttempts] = useState(3);

  const load = useCallback(async () => {
    try {
      const res = await api.listJobs({
        queue: queueFilter || undefined,
        status: statusFilter || undefined,
        search: search || undefined,
        page,
      });
      setJobs(res.data);
      setLastPage(res.last_page);
      setError(null);
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Failed to load jobs.');
    }
  }, [queueFilter, statusFilter, search, page]);

  useEffect(() => {
    api.listQueues().then(setQueues).catch(() => undefined);
  }, []);

  useEffect(() => {
    load();
    const timer = setInterval(load, 5000);
    return () => clearInterval(timer);
  }, [load]);

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    let payload: Record<string, unknown> = {};
    try {
      payload = JSON.parse(formPayload) as Record<string, unknown>;
    } catch {
      setError('Payload is not valid JSON.');
      return;
    }
    try {
      const job = await api.submitJob({
        queue: formQueue,
        handler: formHandler,
        payload,
        priority: formPriority,
        max_attempts: formAttempts,
        delay_seconds: 0,
      });
      setMessage(`Job #${job.id} submitted to "${job.queue_name}".`);
      setError(null);
      setPage(1);
      await load();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to submit job.');
    }
  };

  const act = async (fn: () => Promise<unknown>, okMessage: string) => {
    try {
      await fn();
      setMessage(okMessage);
      setError(null);
      await load();
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Action failed.');
    }
  };

  return (
    <div className="space-y-6">
      <h2 className="text-xl font-semibold text-gray-900">Jobs</h2>
      {error && <div className="rounded-md bg-red-50 p-4 text-sm text-red-700">{error}</div>}
      {message && <div className="rounded-md bg-green-50 p-4 text-sm text-green-700">{message}</div>}

      <form onSubmit={submit} className="space-y-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <h3 className="font-semibold text-gray-900">Submit a job</h3>
        <div className="grid gap-3 md:grid-cols-4">
          <label className="flex flex-col text-sm font-medium text-gray-700">
            Queue
            <select value={formQueue} onChange={(e) => setFormQueue(e.target.value)} className="mt-1 rounded-md border border-gray-300 px-3 py-2">
              {queues.map((q) => (
                <option key={q.id} value={q.name}>{q.name}</option>
              ))}
              {queues.length === 0 && <option value="default">default</option>}
            </select>
          </label>
          <label className="flex flex-col text-sm font-medium text-gray-700">
            Handler
            <select value={formHandler} onChange={(e) => setFormHandler(e.target.value)} className="mt-1 rounded-md border border-gray-300 px-3 py-2">
              {HANDLERS.map((h) => (
                <option key={h} value={h}>{h}</option>
              ))}
            </select>
          </label>
          <label className="flex flex-col text-sm font-medium text-gray-700">
            Priority (1-10)
            <input type="number" min={1} max={10} value={formPriority} onChange={(e) => setFormPriority(Number(e.target.value))} className="mt-1 rounded-md border border-gray-300 px-3 py-2" />
          </label>
          <label className="flex flex-col text-sm font-medium text-gray-700">
            Max attempts
            <input type="number" min={1} max={10} value={formAttempts} onChange={(e) => setFormAttempts(Number(e.target.value))} className="mt-1 rounded-md border border-gray-300 px-3 py-2" />
          </label>
        </div>
        <label className="flex flex-col text-sm font-medium text-gray-700">
          Payload (JSON)
          <textarea value={formPayload} onChange={(e) => setFormPayload(e.target.value)} rows={4}
            className="mt-1 rounded-md border border-gray-300 px-3 py-2 font-mono text-sm" />
        </label>
        <button type="submit" className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
          Submit job
        </button>
      </form>

      <div className="flex flex-wrap gap-3">
        <select value={queueFilter} onChange={(e) => { setQueueFilter(e.target.value); setPage(1); }} className="rounded-md border border-gray-300 px-3 py-2 text-sm">
          <option value="">All queues</option>
          {queues.map((q) => (
            <option key={q.id} value={q.name}>{q.name}</option>
          ))}
        </select>
        <select value={statusFilter} onChange={(e) => { setStatusFilter(e.target.value); setPage(1); }} className="rounded-md border border-gray-300 px-3 py-2 text-sm">
          <option value="">All statuses</option>
          {STATUSES.map((s) => (
            <option key={s} value={s}>{STATUS_LABELS[s]}</option>
          ))}
        </select>
        <input value={search} onChange={(e) => { setSearch(e.target.value); setPage(1); }} placeholder="Search handler or queue"
          className="rounded-md border border-gray-300 px-3 py-2 text-sm" />
      </div>

      <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <JobTable
          jobs={jobs}
          onSelect={onSelectJob}
          onRetry={(job) => act(() => api.retryJob(job.id), `Job #${job.id} queued for retry.`)}
          onCancel={(job) => act(() => api.cancelJob(job.id), `Job #${job.id} cancelled.`)}
        />
        <div className="mt-4 flex items-center justify-between text-sm">
          <button disabled={page <= 1} onClick={() => setPage(page - 1)}
            className="rounded bg-gray-200 px-3 py-1.5 font-medium text-gray-700 disabled:opacity-50">Previous</button>
          <span className="text-gray-500">Page {page} of {lastPage}</span>
          <button disabled={page >= lastPage} onClick={() => setPage(page + 1)}
            className="rounded bg-gray-200 px-3 py-1.5 font-medium text-gray-700 disabled:opacity-50">Next</button>
        </div>
      </div>
    </div>
  );
}
