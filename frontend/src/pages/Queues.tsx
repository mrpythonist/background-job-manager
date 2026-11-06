import { useCallback, useEffect, useState } from 'react';
import { api } from '../api';
import type { Queue } from '../types';
import QueueCard from '../components/QueueCard';

export default function Queues() {
  const [queues, setQueues] = useState<Queue[]>([]);
  const [name, setName] = useState('');
  const [priority, setPriority] = useState(5);
  const [concurrency, setConcurrency] = useState(1);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);

  const load = useCallback(async () => {
    try {
      setQueues(await api.listQueues());
      setError(null);
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Failed to load queues.');
    }
  }, []);

  useEffect(() => {
    load();
    const timer = setInterval(load, 5000);
    return () => clearInterval(timer);
  }, [load]);

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

  const create = async (e: React.FormEvent) => {
    e.preventDefault();
    await act(() => api.createQueue({ name: name.trim(), priority, concurrency }), `Queue "${name.trim()}" created.`);
    setName('');
  };

  return (
    <div className="space-y-6">
      <h2 className="text-xl font-semibold text-gray-900">Queues</h2>
      {error && <div className="rounded-md bg-red-50 p-4 text-sm text-red-700">{error}</div>}
      {message && <div className="rounded-md bg-green-50 p-4 text-sm text-green-700">{message}</div>}

      <form onSubmit={create} className="flex flex-wrap items-end gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <label className="flex flex-col text-sm font-medium text-gray-700">
          Name
          <input value={name} onChange={(e) => setName(e.target.value)} required pattern="[A-Za-z0-9_-]+"
            placeholder="emails" className="mt-1 rounded-md border border-gray-300 px-3 py-2" />
        </label>
        <label className="flex flex-col text-sm font-medium text-gray-700">
          Priority (1-10)
          <input type="number" min={1} max={10} value={priority} onChange={(e) => setPriority(Number(e.target.value))}
            className="mt-1 rounded-md border border-gray-300 px-3 py-2" />
        </label>
        <label className="flex flex-col text-sm font-medium text-gray-700">
          Concurrency
          <input type="number" min={1} max={50} value={concurrency} onChange={(e) => setConcurrency(Number(e.target.value))}
            className="mt-1 rounded-md border border-gray-300 px-3 py-2" />
        </label>
        <button type="submit" className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
          Create queue
        </button>
      </form>

      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        {queues.map((q) => (
          <QueueCard
            key={q.id}
            queue={q}
            onPause={(queue) => act(() => api.pauseQueue(queue.id), `Queue "${queue.name}" paused.`)}
            onResume={(queue) => act(() => api.resumeQueue(queue.id), `Queue "${queue.name}" resumed.`)}
            onDelete={(queue) => act(() => api.deleteQueue(queue.id), `Queue "${queue.name}" deleted.`)}
          />
        ))}
      </div>
    </div>
  );
}
