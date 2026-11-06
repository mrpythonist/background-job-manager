export type JobStatus = 'pending' | 'processing' | 'completed' | 'failed' | 'cancelled' | 'retrying';

export interface JobAttempt {
  id: number;
  job_id: number;
  attempt_number: number;
  worker_id: number | null;
  status: string;
  started_at: string | null;
  finished_at: string | null;
  duration_ms: number | null;
  error: string | null;
}

export interface Job {
  id: number;
  queue_id: number;
  queue_name: string;
  handler: string;
  payload: Record<string, unknown>;
  status: JobStatus;
  priority: number;
  attempts: number;
  max_attempts: number;
  delay_seconds: number;
  started_at: string | null;
  finished_at: string | null;
  duration_ms: number | null;
  last_error: string | null;
  created_at: string;
  updated_at: string;
  job_attempts?: JobAttempt[];
  queue?: Queue;
}

export interface Queue {
  id: number;
  name: string;
  priority: number;
  concurrency: number;
  is_paused: boolean;
  jobs_count?: number;
  pending_count?: number;
  processing_count?: number;
  completed_count?: number;
  failed_count?: number;
  created_at: string;
  updated_at: string;
  jobs?: Job[];
}

export type WorkerStatus = 'idle' | 'busy' | 'stale' | 'offline';

export interface Worker {
  id: number;
  name: string;
  hostname: string | null;
  status: WorkerStatus;
  current_job_id: number | null;
  current_job?: Job | null;
  queues: string[];
  processed_jobs: number;
  failed_jobs: number;
  last_heartbeat_at: string | null;
  started_at: string | null;
}

export interface OverviewStats {
  total: number;
  by_status: Record<JobStatus, number>;
  throughput_last_hour: number;
  average_duration_ms: number | null;
  failure_rate_percent: number;
}

export interface TimeseriesBucket {
  bucket: string;
  completed: number;
  failed: number;
}

export interface Paginated<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export const HANDLERS = ['SendEmail', 'GenerateReport', 'ResizeImage', 'SyncExternalData'] as const;

export const STATUS_LABELS: Record<JobStatus, string> = {
  pending: 'Pending',
  processing: 'Processing',
  completed: 'Completed',
  failed: 'Failed',
  cancelled: 'Cancelled',
  retrying: 'Retrying',
};
