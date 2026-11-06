# Background Job Manager

A lightweight developer dashboard for monitoring background jobs, queues, workers, and failed tasks. Think of a simplified [Laravel Horizon](https://laravel.com/docs/horizon).

![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)

## What it does

- Submit background jobs through an API (`POST /api/jobs`).
- Manage multiple queues: create, pause, resume, and delete them.
- Track jobs in every state: pending, processing, completed, failed, cancelled, retrying.
- Retry failed jobs with exponential backoff, or cancel pending ones.
- Monitor worker status via heartbeats (idle, busy, stale, offline).
- View job execution duration and per-attempt history.
- Display queue statistics: totals by status, throughput, average duration, failure rate, and an hourly time series.

## Tech stack

- **Backend:** Laravel 11 (PHP 8.2+), PostgreSQL 16, Redis 7 (Laravel queues)
- **Frontend:** React 18 + Vite + TypeScript + Tailwind CSS 4
- **Infra:** Docker Compose (API, worker, frontend, Postgres, Redis)

## Architecture

```
React dashboard ──HTTP──> Laravel API ──dispatch──> Redis queue ──> queue:work workers
                              │  ▲
                              │  └─ durable record (jobs, attempts, workers, queues)
                              ▼
                         PostgreSQL
```

Redis holds the transient work queue. PostgreSQL holds the durable record, because the dashboard queries are relational (filter by queue and status, aggregate per queue, time series) and because history should survive a Redis restart. Delivery is **at-least-once**, never exactly-once: a worker can crash after doing the work but before marking the job complete, and the job will run again. Handlers must be idempotent where it matters. See `docs/ARCHITECTURE.md` and `docs/DECISIONS.md`.

Handlers are **simulated in v0.1**: the allowlisted registry (`SendEmail`, `GenerateReport`, `ResizeImage`, `SyncExternalData`) sleeps for `simulate.duration_ms` and optionally throws `simulate.fail_message`. No real email is sent and no real file is written. This keeps the demo runnable without external services; real implementations belong behind the same registry interface.

## Quickstart (Docker Compose)

```bash
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env
docker compose up --build
```

- Dashboard: http://localhost:5173
- API: http://localhost:8000/api/health
- Seed demo data: `docker compose exec api php artisan migrate --seed`
- Dispatch sample jobs: `docker compose exec api php artisan jobs:dispatch-sample`

## Quickstart (without Docker)

```bash
# Postgres 16 and Redis 7 running locally, then:
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve            # API on http://localhost:8000
php artisan queue:work redis --queue=default,emails,reports,images,sync --tries=3

cd ../frontend
npm install
npm run dev                  # dashboard on http://localhost:5173
```

## API

Full reference: `docs/API.md`. The main endpoints:

| Method | Endpoint | Description |
| --- | --- | --- |
| `GET` | `/api/health` | Health check |
| `GET` | `/api/jobs?queue=&status=&search=` | List jobs (paginated, filtered) |
| `POST` | `/api/jobs` | Submit a job |
| `GET` | `/api/jobs/{id}` | Job detail with attempts |
| `POST` | `/api/jobs/{id}/retry` | Retry a failed job (exponential backoff) |
| `POST` | `/api/jobs/{id}/cancel` | Cancel a pending job |
| `GET` | `/api/queues` | List queues with job counts |
| `POST` | `/api/queues` | Create a queue |
| `POST` | `/api/queues/{id}/pause` | Pause a queue |
| `POST` | `/api/queues/{id}/resume` | Resume a queue |
| `DELETE` | `/api/queues/{id}` | Delete an empty queue |
| `GET` | `/api/workers` | List workers with status |
| `GET` | `/api/stats/overview` | Totals, throughput, avg duration, failure rate |
| `GET` | `/api/stats/queues` | Per-queue counts |
| `GET` | `/api/stats/timeseries?hours=24` | Completed/failed per hour |

Example:

```bash
curl -X POST http://localhost:8000/api/jobs \
  -H 'Content-Type: application/json' \
  -d '{"queue":"emails","handler":"SendEmail","payload":{"to":"user@example.com","simulate":{"duration_ms":800}},"priority":5,"max_attempts":3}'
```

More examples: `sample-data/sample-jobs.json`.

### Auth

If `JOB_MANAGER_API_KEY` is set, write requests (`POST`, `PATCH`, `DELETE`) must send the header `X-API-Key: <key>`. Reads stay public. When the key is unset, no auth is required (local demo mode).

## What was verified / not verified

Verified on this machine (2026-11-06):

- `php -l` clean on all 37 backend PHP files (`php backend/scripts/verify.php`).
- `php backend/scripts/smoke.php` passes (14 checks: status counts, failure rate, average duration, throughput, backoff delays, time series, empty-input handling).
- `composer.json` and `package.json` parse as valid JSON; `.env.example` covers the backend keys used by `docker-compose.yml`.
- Frontend: real `npm install` + `npm run build` (TypeScript + Vite) succeeds.

Not verified live, and not claimed:

- Docker build/run was never executed here (no Docker on this machine). The Compose file and Dockerfiles are included but untested.
- Migrations were never applied to a live PostgreSQL database; the queue worker was never run against real Redis.
- No end-to-end run of submit -> worker -> completed against live services.
- The PHPUnit tests need `vendor/` (`composer install`) to run; they were not executed here.
- The CI workflow (`.github/workflows/ci.yml`) cannot be pushed through the GitHub API (missing workflows permission). Add it through the GitHub web UI.

## Project layout

```
backend/    Laravel API (models, controllers, jobs, services, migrations, tests)
frontend/   React + Vite + TypeScript dashboard
docs/       ARCHITECTURE.md, API.md, DECISIONS.md
sample-data/  example POST /api/jobs payloads
```

## Contributing

See `CONTRIBUTING.md`. License: MIT.
