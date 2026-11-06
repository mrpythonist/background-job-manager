import { useState } from 'react';
import type { Job } from './types';
import Dashboard from './pages/Dashboard';
import Queues from './pages/Queues';
import Jobs from './pages/Jobs';
import JobDetail from './pages/JobDetail';
import Workers from './pages/Workers';

type View = 'dashboard' | 'queues' | 'jobs' | 'workers';

export default function App() {
  const [view, setView] = useState<View>('dashboard');
  const [selectedJobId, setSelectedJobId] = useState<number | null>(null);

  const selectJob = (job: Job) => {
    setSelectedJobId(job.id);
    setView('jobs');
  };

  const nav = (v: View) => {
    setSelectedJobId(null);
    setView(v);
  };

  const link = (v: View, label: string) => (
    <button
      onClick={() => nav(v)}
      className={`rounded-md px-3 py-2 text-sm font-medium ${
        view === v && selectedJobId === null ? 'bg-indigo-100 text-indigo-700' : 'text-gray-600 hover:bg-gray-100'
      }`}
    >
      {label}
    </button>
  );

  return (
    <div className="min-h-screen bg-gray-50">
      <header className="border-b border-gray-200 bg-white">
        <div className="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-2 px-4 py-3">
          <h1 className="text-lg font-bold text-gray-900">Background Job Manager</h1>
          <nav className="flex gap-1">
            {link('dashboard', 'Dashboard')}
            {link('queues', 'Queues')}
            {link('jobs', 'Jobs')}
            {link('workers', 'Workers')}
          </nav>
        </div>
      </header>
      <main className="mx-auto max-w-6xl px-4 py-6">
        {selectedJobId !== null ? (
          <JobDetail jobId={selectedJobId} onBack={() => setSelectedJobId(null)} />
        ) : view === 'dashboard' ? (
          <Dashboard onSelectJob={selectJob} />
        ) : view === 'queues' ? (
          <Queues />
        ) : view === 'jobs' ? (
          <Jobs onSelectJob={selectJob} />
        ) : (
          <Workers />
        )}
      </main>
      <footer className="mx-auto max-w-6xl px-4 pb-6 text-xs text-gray-400">
        Simplified Laravel Horizon. Jobs are delivered at-least-once; handlers are simulated in v0.1.
      </footer>
    </div>
  );
}
