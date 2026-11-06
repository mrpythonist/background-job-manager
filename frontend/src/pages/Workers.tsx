import { useCallback, useEffect, useState } from 'react';
import { api } from '../api';
import type { Worker } from '../types';
import WorkerList from '../components/WorkerList';

export default function Workers() {
  const [workers, setWorkers] = useState<Worker[]>([]);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    try {
      setWorkers(await api.listWorkers());
      setError(null);
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Failed to load workers.');
    }
  }, []);

  useEffect(() => {
    load();
    const timer = setInterval(load, 5000);
    return () => clearInterval(timer);
  }, [load]);

  const totalProcessed = workers.reduce((sum, w) => sum + w.processed_jobs, 0);
  const totalFailed = workers.reduce((sum, w) => sum + w.failed_jobs, 0);
  const online = workers.filter((w) => w.status === 'idle' || w.status === 'busy').length;

  return (
    <div className="space-y-6">
      <h2 className="text-xl font-semibold text-gray-900">Workers</h2>
      {error && <div className="rounded-md bg-red-50 p-4 text-sm text-red-700">{error}</div>}
      <div className="grid grid-cols-3 gap-4">
        <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
          <p className="text-sm text-gray-500">Online</p>
          <p className="text-2xl font-semibold">{online} / {workers.length}</p>
        </div>
        <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
          <p className="text-sm text-gray-500">Processed</p>
          <p className="text-2xl font-semibold">{totalProcessed}</p>
        </div>
        <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
          <p className="text-sm text-gray-500">Failed</p>
          <p className="text-2xl font-semibold">{totalFailed}</p>
        </div>
      </div>
      <p className="text-sm text-gray-500">
        Workers register themselves and send a heartbeat while running. A worker with no heartbeat
        for 60 seconds shows as stale, and <code>php artisan workers:prune-stale</code> marks stale
        workers offline (it also runs on a schedule).
      </p>
      <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <WorkerList workers={workers} />
      </div>
    </div>
  );
}
