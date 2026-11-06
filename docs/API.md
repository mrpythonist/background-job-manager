# API Reference

Base URL: `http://localhost:8000/api`

Auth: if `JOB_MANAGER_API_KEY` is set, write requests (`POST`, `PATCH`, `DELETE`) must send the header `X-API-Key: <key>`. Reads are public. When the key is unset, no auth is required.

All responses are JSON. Errors use `{ "message": "...", "errors": { ... } }` with standard HTTP status codes (422 for validation).

## Jobs

### List jobs

`GET /api/jobs?queue=emails&status=failed&search=report&page=1&per_page=15`

Filters: `queue` (name), `status` (`pending|processing|completed|failed|cancelled|retrying`), `search` (matches handler and payload text). Paginated, newest first.

### Get one job

`GET /api/jobs/{id}` — includes its attempts and its queue.

### Submit a job

`POST /api/jobs`

```json
{
  "queue": "emails",
  "handler": "SendEmail",
  "payload": { "to": "user@example.com", "simulate": { "duration_ms": 800 } },
  "priority": 5,
  "max_attempts": 3,
  "delay_seconds": 0
}
```

| Field | Required | Rules |
| --- | --- | --- |
| `queue` | yes | string, must be an existing queue name |
| `handler` | yes | one of `SendEmail`, `GenerateReport`, `ResizeImage`, `SyncExternalData` |
| `payload` | no | object, defaults to `{}` |
| `priority` | no | integer 1-10, default 5 (1 is highest) |
| `max_attempts` | no | integer 1-10, default 3 |
| `delay_seconds` | no | integer 0-86400, default 0 |

Returns `201` with the created job.

### Retry a failed job

`POST /api/jobs/{id}/retry` — only for jobs with status `failed`. Re-queues with an incremented attempt and exponential backoff delay. Returns `422` if the job is not failed or `max_attempts` is exhausted.

### Cancel a job

`POST /api/jobs/{id}/cancel` — only for `pending` or `retrying` jobs.

## Queues

| Method | Endpoint | Description |
| --- | --- | --- |
| `GET` | `/api/queues` | List queues with live job counts |
| `POST` | `/api/queues` | Create a queue (`name`, `priority`, `concurrency`) |
| `GET` | `/api/queues/{id}` | One queue with its recent jobs |
| `PATCH` | `/api/queues/{id}` | Update `priority`, `concurrency`, `is_paused` |
| `POST` | `/api/queues/{id}/pause` | Pause a queue |
| `POST` | `/api/queues/{id}/resume` | Resume a queue |
| `DELETE` | `/api/queues/{id}` | Delete an empty queue (422 if jobs remain) |

## Workers

| Method | Endpoint | Description |
| --- | --- | --- |
| `GET` | `/api/workers` | List workers with status and counters |
| `GET` | `/api/workers/{id}` | One worker with its current job |

Worker status is derived from the heartbeat: `idle`, `busy`, `stale` (no heartbeat for 60s), `offline`.

## Statistics

| Method | Endpoint | Description |
| --- | --- | --- |
| `GET` | `/api/stats/overview` | Totals by status, throughput last hour, average duration, failure rate |
| `GET` | `/api/stats/queues` | Per-queue counts by status |
| `GET` | `/api/stats/timeseries?hours=24` | Completed/failed counts per hour bucket |

## Health

`GET /api/health` — returns `{ "status": "ok" }` plus queue and database connectivity when available.
