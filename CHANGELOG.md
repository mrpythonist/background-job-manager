# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.0] - 2026-11-06

Initial public release.

### Added

- Job submission API (`POST /api/jobs`) with queue, handler, payload, priority, max attempts, and optional delay.
- Job tracking in all states: pending, processing, completed, failed, cancelled, retrying, with per-attempt history and execution duration.
- Queue management: create, list, show, update, pause, resume, and delete (delete only when empty).
- Retry for failed jobs with exponential backoff; cancel for pending or retrying jobs.
- Worker heartbeat monitoring with idle, busy, stale, and offline states, plus a `workers:prune-stale` Artisan command.
- Queue statistics: overview totals, per-queue counts, and an hourly completed/failed time series.
- Health endpoint (`GET /api/health`).
- React + Vite + TypeScript + Tailwind dashboard with pages for the overview, jobs, job detail, queues, and workers. The dashboard polls the API every 5 seconds.
- Optional shared API key (`JOB_MANAGER_API_KEY`, `X-API-Key` header) protecting write endpoints.
- Simulated handler registry (`SendEmail`, `GenerateReport`, `ResizeImage`, `SyncExternalData`) with `simulate.duration_ms` and `simulate.fail_message`.
- Docker Compose setup: Postgres, Redis, api, worker, frontend.
- Database seeders with five queues (`default`, `emails`, `reports`, `images`, `sync`) and sample jobs.
- `jobs:dispatch-sample` Artisan command for demo data.
- Pure-PHP verification script (`backend/scripts/verify.php`) and smoke script (`backend/scripts/smoke.php`).
- PHPUnit tests: `Feature/JobApiTest.php`, `Unit/QueueStatsServiceTest.php`.

### Known limitations

- Docker build/run was never executed on the machine that prepared this release (no Docker there). The Compose file and Dockerfiles are included but untested.
- Migrations were never applied to a live PostgreSQL database; the queue worker was never run against real Redis.
- No end-to-end run of submit -> worker -> completed against live services.
- The PHPUnit tests need `vendor/` (`composer install`) to run; they were not executed here.
- Delivery is at-least-once, never exactly-once. Handlers must be idempotent where it matters.
