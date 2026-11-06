const BASE = (import.meta.env.VITE_API_URL as string | undefined) ?? 'http://localhost:8000/api';

async function request<T>(path: string, options: RequestInit = {}): Promise<T> {
  const res = await fetch(`${BASE}${path}`, {
    headers: { 'Content-Type': 'application/json', Accept: 'application/json', ...(options.headers ?? {}) },
    ...options,
  });
  if (!res.ok) {
    const body = await res.json().catch(() => ({}));
    const message = (body as { message?: string }).message ?? `Request failed with status ${res.status}`;
    throw new Error(message);
  }
  if (res.status === 204) {
    return undefined as T;
  }
  return (await res.json()) as T;
}

import type { Job, OverviewStats, Paginated, Queue, TimeseriesBucket, Worker } from './types';

export const api = {
  overview: () => request<OverviewStats>('/stats/overview'),
  queueStats: () => request<Record<string, Record<string, number>>>('/stats/queues'),
  timeseries: (hours = 24) => request<TimeseriesBucket[]>(`/stats/timeseries?hours=${hours}`),

  listJobs: (params: { queue?: string; status?: string; search?: string; page?: number; per_page?: number } = {}) => {
    const q = new URLSearchParams();
    if (params.queue) q.set('queue', params.queue);
    if (params.status) q.set('status', params.status);
    if (params.search) q.set('search', params.search);
    if (params.page) q.set('page', String(params.page));
    if (params.per_page) q.set('per_page', String(params.per_page));
    const suffix = q.toString() ? `?${q.toString()}` : '';
    return request<Paginated<Job>>(`/jobs${suffix}`);
  },
  getJob: (id: number) => request<Job>(`/jobs/${id}`),
  submitJob: (data: { queue: string; handler: string; payload: Record<string, unknown>; priority: number; max_attempts: number; delay_seconds: number }) =>
    request<Job>('/jobs', { method: 'POST', body: JSON.stringify(data) }),
  retryJob: (id: number) => request<Job>(`/jobs/${id}/retry`, { method: 'POST' }),
  cancelJob: (id: number) => request<Job>(`/jobs/${id}/cancel`, { method: 'POST' }),

  listQueues: () => request<Queue[]>('/queues'),
  createQueue: (data: { name: string; priority: number; concurrency: number }) =>
    request<Queue>('/queues', { method: 'POST', body: JSON.stringify(data) }),
  pauseQueue: (id: number) => request<Queue>(`/queues/${id}/pause`, { method: 'POST' }),
  resumeQueue: (id: number) => request<Queue>(`/queues/${id}/resume`, { method: 'POST' }),
  deleteQueue: (id: number) => request<void>(`/queues/${id}`, { method: 'DELETE' }),

  listWorkers: () => request<Worker[]>('/workers'),
  getWorker: (id: number) => request<Worker>(`/workers/${id}`),
};
