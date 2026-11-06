# Screenshots

There are no GUI screenshots of the dashboard here. No headless browser is available on the machine used to prepare v0.1.0, so no dashboard image could be captured honestly, and none was fabricated.

What this folder holds instead is genuine terminal output captured on 2026-11-06 (PHP 8.3, Node.js 24):

- `verify-php-lint.txt` - output of `php backend/scripts/verify.php`: `php -l` clean on all 37 backend PHP files, plus JSON and env coverage checks.
- `smoke-test.txt` - output of `php backend/scripts/smoke.php`: 14 pure-PHP checks (status counts, failure rate, average duration, throughput, backoff delays, time series, empty-input handling).
- `frontend-build.txt` - output of `npm run build` in `frontend/`: TypeScript + Vite production build.
- `frontend-preview-curl.txt` - the built dashboard served by `vite preview` and checked with `curl` (HTTP 200). The note at the end is part of the capture: the API was not running, so the page could not load live data. Postgres and Redis are not available on that machine.

If you run the stack and capture a real dashboard screenshot, add the image file here and reference it from the README.
