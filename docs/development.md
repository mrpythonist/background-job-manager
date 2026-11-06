# Development Guide

How to work on Background Job Manager locally. The README covers installation; this page covers the day-to-day workflow, the commands that exist, and the rules that keep contributions verifiable.

## Prerequisites

- PHP 8.2 or newer (8.3 was used for the v0.1.0 verification) with the `pgsql` and `redis` extensions, and Composer for backend dependencies.
- Node.js 20 or newer with npm for the frontend.
- PostgreSQL 16 and Redis 7, either through Docker Compose or installed locally.
- Docker and Docker Compose for the container path.

## Running the stack

### Docker Compose (recommended)

```bash
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env
docker compose up --build
```

Services: `postgres` (5432), `redis` (6379), `api` (8000), `worker`, `frontend` (5173). Seed demo data with `docker compose exec api php artisan migrate --seed`.

### Without Docker

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

The dashboard reads the API URL from `VITE_API_URL` (default `http://localhost:8000/api`) and polls every 5 seconds.

## Useful commands

Artisan commands defined in this repo:

```bash
php artisan migrate --seed            # create tables, seed 5 queues and sample jobs
php artisan jobs:dispatch-sample --count=5   # dispatch sample jobs across the queues
php artisan workers:prune-stale --stale-after=60  # mark stale workers offline
php artisan queue:work redis --queue=default,emails,reports,images,sync --tries=3 --sleep=3
```

Frontend scripts (`frontend/package.json`):

```bash
npm run dev       # Vite dev server on http://localhost:5173
npm run build     # tsc -b && vite build
npm run preview   # serve the production build locally
```

Composer scripts (`backend/composer.json`):

```bash
composer test     # php artisan test
composer lint     # php -l on app/Services/QueueStatsService.php only (not the whole backend)
```

## Checks to run before a pull request

```bash
# Lint every backend PHP file, validate JSON, check env coverage (no vendor/ needed)
php backend/scripts/verify.php

# 14 pure-PHP smoke checks for stats and backoff logic (no vendor/ needed)
php backend/scripts/smoke.php

# PHPUnit (needs Composer dependencies installed first)
cd backend && composer install && php artisan test

# Frontend typecheck + production build
cd frontend && npm install && npm run build
```

Note the difference between the two lint paths: `composer lint` checks one file only, while `backend/scripts/verify.php` runs `php -l` on all 37 backend PHP files. Run `verify.php` before a PR.

## Project layout

```text
backend/
  app/Http/Controllers/Api/   JobController, QueueController, WorkerController, StatsController
  app/Http/Middleware/        RequireApiKey (X-API-Key on writes, when configured)
  app/Http/Requests/          StoreJobRequest (validation for POST /api/jobs)
  app/Jobs/                   ProcessBackgroundJob (the queued job)
  app/Models/                 Job, JobAttempt, Queue, Worker
  app/Services/               JobDispatcher, QueueStatsService, WorkerRegistry
  app/Console/Commands/       DispatchSampleJobsCommand, PruneStaleWorkersCommand
  config/                     app, cors, database, queue
  database/migrations/        queues, jobs, workers, job_attempts, failed_jobs
  database/factories/         JobFactory, QueueFactory
  database/seeders/           DatabaseSeeder (5 queues, sample jobs, one worker)
  routes/api.php              all API routes
  scripts/verify.php          php -l + JSON + env checks
  scripts/smoke.php           14 pure-PHP checks
  tests/Feature/JobApiTest.php
  tests/Unit/QueueStatsServiceTest.php
frontend/
  src/pages/                  Dashboard, Jobs, JobDetail, Queues, Workers
  src/components/             JobTable, QueueCard, StatCard, StatusBadge, ThroughputChart, WorkerList
  src/api.ts                  API client (base URL from VITE_API_URL)
  src/types.ts                shared TypeScript types
```

## Adding a handler

Handlers live in a fixed allowlist in `backend/app/Services/JobDispatcher.php`. To add one:

1. Add the handler name to the registry and implement its simulated behavior there.
2. Add the name to the `handler` validation rule in `StoreJobRequest` and to the API docs (`docs/api.md` and the README table).
3. Add a test that submits a job with the new handler.
4. Never accept a class name from a request and instantiate it. That would be remote code execution.

## Adding an endpoint

1. Add the route in `backend/routes/api.php` and the controller method.
2. Update `docs/api.md` and the README API tables in the same PR.
3. If the endpoint changes what is verified, update the README "What was verified / not verified" section.

## Verification honesty

This project documents what was actually run. The README keeps a verified / not-verified list, and it must stay accurate:

- Docker build/run, migrations against live Postgres, the worker against real Redis, and the PHPUnit suite were not run on the machine that prepared v0.1.0. Do not claim them until someone runs them and records the result.
- If you run a check, say exactly what you ran, on what versions, and whether Postgres and Redis were live. "Tested" without that detail is not a verification.
- Do not add screenshots, terminal output, or API responses you did not capture. Genuine captures live in `docs/screenshots/`.

## Environment variables

Backend variables are documented in the README configuration table and in `backend/.env.example`. Frontend variables are in `frontend/.env.example` (`VITE_API_URL`). Never commit a real `.env` file; only `.env.example` files belong in the repo.
