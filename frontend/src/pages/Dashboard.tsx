import { useCallback, useEffect, useState } from 'react';
import { api } from '../api';
import type { Job, OverviewStats, Queue, TimeseriesBucket, Worker } from '../types';
import StatCard from '../components/StatCard';
import JobTable from '../components/JobTable';
import QueueCard from '../components/QueueCard';
import WorkerList from '../components/WorkerList';
import ThroughputChart from '../components/ThroughputChart';

export default function Dashboard({ onSelectJob }: { onSelectJob: (job: Job) => void }) {
  const [overview, setOverview] = useState<OverviewStats | null>(null);
  const [queues, setQueues] = useState<Queue[]>([]);
  const [jobs, setJobs] = useState<Job[]>([]);
  const [workers, setWorkers] = useState<Worker[]>([]);
  const [series, setSeries] = useState<TimeseriesBucket[]>([]);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    try {
      const [ov, qs, js, ws, ts] = await Promise.all([
        api.overview(),
        api.listQueues(),
        api.listJobs({ per_page: 10 }),
        api.listWorkers(),
        api.timeseries(24),
      ]);
      setOverview(ov);
      setQueues(qs);
      setJobs(js.data);
      setWorkers(ws);
      setSeries(ts);
      setError(null);
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Failed to load dashboard data. Is the API running?');
    }
  }, []);

  useEffect(() => {
    load();
    const timer = setInterval(load, 5000);
    return () => clearInterval(timer);
  }, [load]);

  const refreshAfter = async (fn: () => Promise<unknown>) => {
    try {
      await fn();
      await load();
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Action failed.');
    }
  };

  return (
    <div className="space-y-6">
      {error && (
        <div className="rounded-md bg-red-50 p-4 text-sm text-red-700">{error}</div>
      )}

      <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <StatCard label="Total jobs" value={overview ? String(overview.total) : '-'} />
        <StatCard
          label="Failed jobs"
          value={overview ? String(overview.by_status.failed) : '-'}
          sub={overview ? `Failure rate ${overview.failure_rate_percent}%` : undefined}
        />
        <StatCard
          label="Throughput (last hour)"
          value={overview ? `${overview.throughput_last_hour} jobs` : '-'}
          sub="Completed jobs"
        />
        <StatCard
          label="Avg duration"
          value={overview?.average_duration_ms != null ? `${Math.round(overview.average_duration_ms)} ms` : '-'}
          sub={overview ? `${overview.by_status.pending} pending · ${overview.by_status.processing} processing` : undefined}
        />
      </div>

      <section className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <h2 className="mb-3 text-lg font-semibold text-gray-900">Throughput</h2>
        <ThroughputChart data={series} />
      </section>

      <section>
        <h2 className="mb-3 text-lg font-semibold text-gray-900">Queues</h2>
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          {queues.map((q) => (
            <QueueCard
              key={q.id}
              queue={q}
              onPause={(queue) => refreshAfter(() => api.pauseQueue(queue.id))}
              onResume={(queue) => refreshAfter(() => api.resumeQueue(queue.id))}
              onDelete={(queue) => refreshAfter(() => api.deleteQueue(queue.id))}
            />
          ))}
        </div>
        {queues.length === 0 && <p className="text-sm text-gray-500">No queues yet. Create one from the Queues page.</p>}
      </section>

      <section className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <h2 className="mb-3 text-lg font-semibold text-gray-900">Recent jobs</h2>
        <JobTable
          jobs={jobs}
          onSelect={onSelectJob}
          onRetry={(job) => refreshAfter(() => api.retryJob(job.id))}
          onCancel={(job) => refreshAfter(() => api.cancelJob(job.id))}
        />
      </section>

      <section className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <h2 className="mb-3 text-lg font-semibold text-gray-900">Workers</h2>
        <WorkerList workers={workers} />
      </section>
    </div>
  );
}
