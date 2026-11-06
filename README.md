# Background Job Manager

A lightweight dashboard for submitting, tracking, and retrying background jobs. A simplified [Laravel Horizon](https://laravel.com/docs/horizon).

![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)

## The problem

Background jobs are easy to dispatch and hard to see. When a job fails at 2 AM, the usual answers are "check the logs" or "check Redis", which tells you nothing about which job failed, how many times it was tried, or what it was supposed to do.

Background Job Manager gives jobs a durable record and a dashboard: every job is stored in PostgreSQL with its status, attempts, and duration, while Redis holds only the transient work queue. You can see pending, processing, completed, failed, and cancelled jobs, retry the failed ones, pause a queue, and watch worker health, all from one place.

## Key features

Implemented in v0.1.0:

- Submit jobs through a JSON API (`POST /api/jobs`) with queue, handler, payload, priority, max attempts, and optional delay.
- Multiple queues with create, pause, resume, update, and delete. Seeded queues: `default`, `emails`, `reports`, `images`, `sync`.
- Job tracking in every state: `pending`, `processing`, `completed`, `failed`, `cancelled`, `retrying`, with per-attempt history and execution duration.
- Retry failed jobs with exponential backoff. Backoff delays double per attempt (10s, 20s, 40s, 80s in the smoke test).
- Worker heartbeat monitoring. Workers report as `idle`, `busy`, `stale` (no heartbeat for 60 seconds by default), or `offline` after pruning.
- Queue statistics: totals by status, throughput, average duration, failure rate, per-queue counts, and an hourly completed/failed time series.
- React dashboard that polls the API every 5 seconds, with pages for the overview, jobs, job detail, queues, and workers.
- Optional shared API key for write endpoints (`X-API-Key` header). Reads stay public. When no key is set, no auth is required.

What v0.1.0 does not do, on purpose: handlers are simulated. The allowlisted registry (`SendEmail`, `GenerateReport`, `ResizeImage`, `SyncExternalData`) sleeps for `simulate.duration_ms` and optionally throws `simulate.fail_message`. No real email is sent, no real file is written, and no real external API is called. This keeps the demo runnable without external services. Real handler implementations belong behind the same registry interface. See `docs/DECISIONS.md` for the reasoning.

Delivery is at-least-once, never exactly-once. A worker can crash after doing the work but before marking the job complete, and the job will run again. Handlers must be idempotent where it matters.

## Screenshots

There are no GUI screenshots yet: no headless browser is available on the machine used to prepare this release, so no dashboard image could be captured honestly. What exists instead is genuine terminal output, saved under `docs/screenshots/`:

- `verify-php-lint.txt` - `php -l` clean on all 37 backend PHP files, plus JSON and env checks.
- `smoke-test.txt` - 14 pure-PHP smoke checks (status counts, failure rate, average duration, throughput, backoff delays, time series).
- `frontend-build.txt` - real `npm run build` output (TypeScript + Vite).
- `frontend-preview-curl.txt` - the built dashboard HTML served by `vite preview`, verified with `curl` (HTTP 200).

If you run the stack and capture a real dashboard screenshot, a pull request adding it here is welcome.

## Architecture

```text
React dashboard ──HTTP──> Laravel API ──dispatch──> Redis queue ──> queue:work workers
                              │  ▲
                              │  └─ durable record (jobs, attempts, workers, queues)
                              ▼
                         PostgreSQL
```

Redis holds the transient work queue. PostgreSQL holds the durable record, because the dashboard queries are relational (filter by queue and status, aggregate per queue, time series) and because history should survive a Redis restart.

Full detail: `docs/architecture.md`. Design decisions: `docs/DECISIONS.md`.

## Tech stack

- Backend: Laravel 11 (PHP 8.2+), PostgreSQL 16, Redis 7 (Laravel queues, `predis/predis`)
- Frontend: React 18 + Vite 6 + TypeScript + Tailwind CSS 4 (charts are hand-drawn SVG, no chart library)
- Infra: Docker Compose (api, worker, frontend, Postgres, Redis)

## Prerequisites

- Docker and Docker Compose (recommended path), or:
- PHP 8.2 or newer with the `pgsql` and `redis` extensions, Composer, Node.js 20 or newer with npm, PostgreSQL 16, and Redis 7 running locally

## Installation

### With Docker Compose

```bash
git clone https://github.com/mrpythonist/background-job-manager.git
cd background-job-manager
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env
docker compose up --build
```

- Dashboard: http://localhost:5173
- API: http://localhost:8000/api/health
- Seed demo data: `docker compose exec api php artisan migrate --seed`
- Dispatch sample jobs: `docker compose exec api php artisan jobs:dispatch-sample`

### Without Docker

PostgreSQL 16 and Redis 7 running locally, then:

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve            # API on http://localhost:8000
php artisan queue:work redis --queue=default,emails,reports,images,sync --tries=3 --sleep=3

cd ../frontend
npm install
npm run dev                  # dashboard on http://localhost:5173
```

## Configuration

Backend variables live in `backend/.env` (see `backend/.env.example`). Frontend variables live in `frontend/.env` (see `frontend/.env.example`).

| Variable | Default | What it does |
| --- | --- | --- |
| `APP_NAME` | `Background Job Manager` | Application name |
| `APP_ENV` | `local` | Laravel environment |
| `APP_KEY` | (empty) | Laravel app key; generate with `php artisan key:generate` |
| `APP_DEBUG` | `true` | Laravel debug mode; set `false` outside local development |
| `APP_URL` | `http://localhost:8000` | API base URL |
| `DB_CONNECTION` | `pgsql` | Database driver |
| `DB_HOST` | `127.0.0.1` | Postgres host (`postgres` under Compose) |
| `DB_PORT` | `5432` | Postgres port |
| `DB_DATABASE` | `background_jobs` | Postgres database name |
| `DB_USERNAME` | `bjm` | Postgres user |
| `DB_PASSWORD` | `change-me-in-production` | Postgres password |
| `REDIS_HOST` | `127.0.0.1` | Redis host (`redis` under Compose) |
| `REDIS_PORT` | `6379` | Redis port |
| `REDIS_PASSWORD` | (empty) | Redis password, if set |
| `QUEUE_CONNECTION` | `redis` | Queue driver (`database` works as a fallback without Redis) |
| `JOB_MANAGER_API_KEY` | (empty) | When set, write endpoints require header `X-API-Key: <key>` |
| `WORKER_STALE_AFTER_SECONDS` | `60` | Seconds without a heartbeat before a worker is stale |
| `FRONTEND_URL` | `http://localhost:5173` | Dashboard URL, used for CORS |
| `VITE_API_URL` | `http://localhost:8000/api` | API base URL used by the frontend |
| `API_PORT` | `8000` | Host port for the API under Compose |
| `FRONTEND_PORT` | `5173` | Host port for the dashboard under Compose |

## Quick start

With the stack running, submit a job:

```bash
curl -X POST http://localhost:8000/api/jobs \
  -H 'Content-Type: application/json' \
  -d '{"queue":"emails","handler":"SendEmail","payload":{"to":"user@example.com","simulate":{"duration_ms":800}},"priority":5,"max_attempts":3}'
```

Then open http://localhost:5173 and watch the job move from pending to completed. More example payloads: `sample-data/sample-jobs.json`. Dispatch five sample jobs at once with `php artisan jobs:dispatch-sample --count=5`.

## Usage and API reference

Base URL: `http://localhost:8000/api`. All responses are JSON. Errors use `{ "message": "...", "errors": { ... } }` with standard HTTP status codes (422 for validation).

Auth: if `JOB_MANAGER_API_KEY` is set, write requests (`POST`, `PATCH`, `DELETE`) must send the header `X-API-Key: <key>`. Reads are public. When the key is unset, no auth is required.

Full reference: `docs/api.md`.

### Jobs

| Method | Endpoint | Description |
| --- | --- | --- |
| `GET` | `/api/jobs?queue=&status=&search=&page=&per_page=` | List jobs, paginated, newest first |
| `POST` | `/api/jobs` | Submit a job (returns 201) |
| `GET` | `/api/jobs/{id}` | Job detail with attempts and queue |
| `POST` | `/api/jobs/{id}/retry` | Retry a failed job (422 if not failed or attempts exhausted) |
| `POST` | `/api/jobs/{id}/cancel` | Cancel a pending or retrying job |

`POST /api/jobs` fields:

| Field | Required | Rules |
| --- | --- | --- |
| `queue` | yes | string, must be an existing queue name |
| `handler` | yes | one of `SendEmail`, `GenerateReport`, `ResizeImage`, `SyncExternalData` |
| `payload` | no | object, defaults to `{}` |
| `priority` | no | integer 1-10, default 5 (1 is highest) |
| `max_attempts` | no | integer 1-10, default 3 |
| `delay_seconds` | no | integer 0-86400, default 0 |

### Queues

| Method | Endpoint | Description |
| --- | --- | --- |
| `GET` | `/api/queues` | List queues with live job counts |
| `POST` | `/api/queues` | Create a queue (`name`, `priority`, `concurrency`) |
| `GET` | `/api/queues/{id}` | One queue with its recent jobs |
| `PATCH` | `/api/queues/{id}` | Update `priority`, `concurrency`, `is_paused` |
| `POST` | `/api/queues/{id}/pause` | Pause a queue |
| `POST` | `/api/queues/{id}/resume` | Resume a queue |
| `DELETE` | `/api/queues/{id}` | Delete an empty queue (422 if jobs remain) |

### Workers

| Method | Endpoint | Description |
| --- | --- | --- |
| `GET` | `/api/workers` | List workers with status and counters |
| `GET` | `/api/workers/{id}` | One worker with its current job |

### Statistics

| Method | Endpoint | Description |
| --- | --- | --- |
| `GET` | `/api/stats/overview` | Totals by status, throughput last hour, average duration, failure rate |
| `GET` | `/api/stats/queues` | Per-queue counts by status |
| `GET` | `/api/stats/timeseries?hours=24` | Completed/failed counts per hour bucket |

### Health

`GET /api/health` returns `{ "status": "ok" }`.

## Project structure

```text
backend/        Laravel 11 API
  app/          Controllers, Jobs, Models, Services, Console commands
  config/       app, cors, database, queue
  database/     migrations, factories, seeders
  routes/       api.php, console.php
  scripts/      verify.php (php -l + JSON + env checks), smoke.php (14 pure-PHP checks)
  tests/        Feature/JobApiTest.php, Unit/QueueStatsServiceTest.php
frontend/       React + Vite + TypeScript dashboard
  src/pages/    Dashboard, Jobs, JobDetail, Queues, Workers
  src/components/  JobTable, QueueCard, StatCard, StatusBadge, ThroughputChart, WorkerList
docs/           architecture.md, api.md, development.md, ARCHITECTURE.md, API.md, DECISIONS.md
sample-data/    example POST /api/jobs payloads
docker-compose.yml
```

## Testing

No test command is required to use the project, but these checks exist:

```bash
# Lint every backend PHP file and validate JSON + env coverage (no vendor/ needed)
php backend/scripts/verify.php

# 14 pure-PHP smoke checks for the stats service and backoff logic (no vendor/ needed)
php backend/scripts/smoke.php

# PHPUnit (needs Composer dependencies installed first)
cd backend && composer install && php artisan test
# or: cd backend && vendor/bin/phpunit

# Frontend typecheck + production build
cd frontend && npm install && npm run build
```

## Deployment

This is a v0.1.0 developer tool, not a hardened production service. The Docker Compose setup is the deployment path that exists today: five services (Postgres, Redis, api, worker, frontend) with healthchecks on Postgres and Redis and named volumes for data.

Before using it beyond local development:

- Set a strong `DB_PASSWORD`, `REDIS_PASSWORD`, and `APP_KEY`; set `APP_DEBUG=false` and `APP_ENV=production`.
- Set `JOB_MANAGER_API_KEY` so write endpoints are not open.
- Put the API behind TLS and a reverse proxy; the Compose file serves plain HTTP on port 8000.
- Run the worker as a supervised process (`queue:work` with `--tries=3`) and schedule `php artisan workers:prune-stale` so dead workers are marked offline.
- Back up the Postgres volume. Job history lives there, not in Redis.

## Security

- Never run an arbitrary handler class name from a request. Handlers live in a fixed allowlist in `JobDispatcher`; adding one is a code change plus a test.
- The API key is a single shared key for write endpoints only. It is not per-user auth; see `docs/DECISIONS.md` for why Sanctum was left out of v0.1.
- Do not commit real `.env` files. Only `.env.example` files belong in the repo.
- To report a vulnerability, see `SECURITY.md`.

## What was verified / not verified

Verified 2026-11-06 on the machine used to prepare this release (PHP 8.3, Node.js 24):

- `php -l` clean on all 37 backend PHP files (`php backend/scripts/verify.php`). Output: `docs/screenshots/verify-php-lint.txt`.
- `php backend/scripts/smoke.php` passes (14 checks). Output: `docs/screenshots/smoke-test.txt`.
- `composer.json` and `package.json` parse as valid JSON; `.env.example` covers the backend keys used by `docker-compose.yml`.
- Frontend: real `npm install` + `npm run build` (TypeScript + Vite) succeeds. Output: `docs/screenshots/frontend-build.txt`.
- The built dashboard HTML serves under `vite preview` and returns HTTP 200. Output: `docs/screenshots/frontend-preview-curl.txt`.

Not verified live, and not claimed:

- Docker build/run was never executed here (no Docker on this machine). The Compose file and Dockerfiles are included but untested.
- Migrations were never applied to a live PostgreSQL database; the queue worker was never run against real Redis.
- No end-to-end run of submit -> worker -> completed against live services.
- The PHPUnit tests need `vendor/` (`composer install`) to run; they were not executed here.
- The CI workflow (`.github/workflows/ci.yml`) was added through the GitHub web UI; workflow files cannot be pushed through the GitHub API.

## Roadmap

From `docs/DECISIONS.md`, what v0.1 deliberately left out, in no promised order:

- Real handler side effects (email, files, image processing, external APIs) behind the registry interface.
- User accounts and per-user tokens (Laravel Sanctum).
- Scheduled and recurring jobs.
- Horizontal worker autoscaling.
- Metrics export (Prometheus) and alerting.
- End-to-end verification against live Postgres and Redis, and a Docker run verified on a machine with Docker.

## Contributing

See `CONTRIBUTING.md`. Bug reports and feature requests use the issue templates in `.github/ISSUE_TEMPLATE/`.

## License

MIT. See `LICENSE`.
