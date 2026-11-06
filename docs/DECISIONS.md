# Decisions

## Laravel queues + Redis as the queue backend

Laravel's queue system is a good fit for a PHP team: one API (`dispatch`, `ShouldQueue`, `queue:work`) covers the database, Redis, and SQS drivers, and Horizon-style tooling is the mental model this project simplifies. Redis was chosen over the database driver because queue throughput, delayed jobs, and blocking pops are native to Redis, and because the dashboard needs to show queue depth cheaply. The database driver remains available as a fallback (`QUEUE_CONNECTION=database`) for environments without Redis.

## PostgreSQL as the system of record

See `ARCHITECTURE.md`. Short version: Redis for the queue, Postgres for the record. The dashboard queries are relational (filter by queue and status, aggregate per queue, time series), and Postgres answers them with indexes instead of scans over Redis keys.

## Allowlisted handler registry, not dynamic class names

Accepting a class name from an API request and instantiating it would be a remote code execution hole. Handlers live in a fixed registry in `JobDispatcher`. Adding a handler is a code change plus a test, never a request parameter.

## Small API-key middleware instead of Sanctum

The API is a developer tool, not a multi-user product in v0.1. A single shared key (`JOB_MANAGER_API_KEY`, header `X-API-Key`) protects write endpoints when set, and does nothing when unset so local demos work out of the box. Token-per-user auth (Sanctum) is the obvious next step if this grows users.

## Pure-PHP QueueStatsService

Statistics are computed in a class with no framework dependency: arrays in, arrays out. That makes the logic unit-testable and smoke-testable with plain PHP, without booting Laravel or installing vendor packages. The controller is a thin adapter that feeds Eloquent results into it.

## React + Vite + Tailwind, charts in plain SVG

The dashboard polls the API every 5 seconds. Charts are hand-drawn SVG (no chart library) to keep the dependency list small and the build fast. If charting needs grow, a library can replace the SVG component without touching the API layer.

## At-least-once, never exactly-once

See `ARCHITECTURE.md`. The project documents the guarantee it actually provides.

## What was deliberately left out of v0.1

- Real handler side effects (email, files, image processing, external APIs). Handlers are simulated.
- User accounts and per-user tokens.
- Scheduled/recurring jobs.
- Horizontal worker autoscaling.
- Metrics export (Prometheus) and alerting.
