# Architecture

```
                    +------------------+
                    |  React dashboard |
                    |  (Vite + TS)     |
                    +--------+---------+
                             | HTTP /api
                             v
+----------+      +------------------+      +------------------+
|  Client  +----->|  Laravel 11 API  |----->|  PostgreSQL 16   |
|  (curl / |      |  Jobs, Queues,   |      |  jobs, queues,   |
|   SDK)   |      |  Workers, Stats  |      |  attempts,       |
+----------+      +--------+---------+      |  workers,        |
                             |               |  failed_jobs     |
                    dispatch |               +------------------+
                             v
                    +------------------+
                    |  Redis 7         |
                    |  queue backend   |
                    +--------+---------+
                             |
                    +------------------+
                    |  queue:work      |
                    |  workers         |
                    +------------------+
```

## How a job flows

1. A client POSTs to `/api/jobs` with a queue name, a handler name, a payload, a priority and a max attempt count.
2. `StoreJobRequest` validates the input. The handler must be in the allowlist in `JobDispatcher`; arbitrary class names from a request are never executed.
3. A `jobs` row is created with status `pending` and the job is pushed onto the named Redis queue as a `ProcessBackgroundJob` instance.
4. A worker picks the job up, marks it `processing`, records `started_at`, runs the handler, and records an attempt in `job_attempts`.
5. On success the job becomes `completed` with `finished_at` and `duration_ms`. On failure the worker releases the job with exponential backoff (`2^attempt * base seconds`) until `max_attempts` is reached, then marks it `failed` and writes a row to `failed_jobs` (the Laravel convention).
6. Retrying a failed job from the dashboard re-queues it with `status = retrying`, an incremented attempt count, and a fresh backoff delay.

## Why PostgreSQL for job records, not Redis only

Redis is fast and is the right place for the queue itself, but it is a poor system of record: data can be evicted, it has no relational queries, and rebuilding history after a restart is painful. PostgreSQL stores the durable record (job, attempts, workers, queues) so the dashboard can filter, search, aggregate statistics, and keep an audit trail. Redis holds only the transient work queue.

## Delivery semantics: at-least-once

Jobs are delivered at-least-once, not exactly-once. A worker can crash after doing the work but before marking the job complete, and the job will then run again. Handlers must therefore be idempotent where it matters (for example, check whether the email was already sent before sending it again). This project never claims exactly-once delivery.

## Workers and heartbeats

Each worker process registers itself in the `workers` table and updates `last_heartbeat_at` while it runs. A worker is `idle` when it has no current job, `busy` while processing one, `stale` when its heartbeat is older than the configured threshold (default 60 seconds), and `offline` after `workers:prune-stale` marks stale workers offline. The dashboard shows processed and failed counts per worker.

## What is simulated in v0.1

The four handlers (`SendEmail`, `GenerateReport`, `ResizeImage`, `SyncExternalData`) are simulated: they sleep for `simulate.duration_ms` and optionally throw the configured `simulate.fail_message`. They do not send real emails, generate real files, resize real images, or call real external APIs. The simulation is deterministic and exists so the dashboard, retries, backoff, and statistics can be demoed without external services. Real handler implementations belong behind the same registry interface.
