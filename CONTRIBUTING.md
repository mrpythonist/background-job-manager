# Contributing to Background Job Manager

Thanks for your interest. This project is a simplified Laravel Horizon: a Laravel 11 API plus a React dashboard for submitting, tracking, and retrying background jobs. Contributions that keep it small, honest, and verified are welcome.

## Code of conduct

Please read `CODE_OF_CONDUCT.md`. Be direct, be respectful, and assume good intent.

## Getting started

1. Fork the repo and clone your fork.
2. Copy the example env files (never commit real ones):
   ```bash
   cp backend/.env.example backend/.env
   cp frontend/.env.example frontend/.env
   ```
3. Start the stack with `docker compose up --build`, or run the API and frontend separately (see the README, "Installation").
4. Seed demo data: `docker compose exec api php artisan migrate --seed`.
5. Make your change on a branch. See "Branches and commits" below.

Development detail, including the commands that actually exist in this repo, is in `docs/development.md`.

## Branches and commits

- Branch from `main`. Name the branch after the change: `fix/retry-422-message`, `feat/queue-pause-button`, `docs/api-timeseries`.
- One logical change per pull request. Small PRs get reviewed faster.
- Commit messages: short imperative summary, one line, for example `Fix retry returning 200 for cancelled jobs`. Add a body only when the why is not obvious from the diff.
- Do not commit generated files, `vendor/`, `node_modules/`, `dist/`, or any `.env` file. Check `git status` before committing.

## Tests

Run the checks that apply to your change before opening a pull request:

```bash
# Always: lint all backend PHP files, validate JSON, check env coverage
php backend/scripts/verify.php

# Always, if you touched stats or backoff logic: 14 pure-PHP smoke checks
php backend/scripts/smoke.php

# Backend tests (needs Composer dependencies installed first)
cd backend && composer install && php artisan test

# Frontend (needs npm install first)
cd frontend && npm install && npm run build
```

The PHPUnit tests need `vendor/` and were not run on the machine that prepared v0.1.0, because Composer was not available there. If you run them, say so in the PR description, including the PHP version and whether Postgres and Redis were live.

## Rules

- Keep the code typed and readable. PHP 8.2+ syntax, TypeScript strict mode.
- Do not add a dependency without a reason in the PR description.
- Do not claim a feature works end to end unless you ran it against real Postgres and Redis. If you only ran the lint and smoke scripts, say that.
- Job handlers must stay in the allowlisted registry in `backend/app/Services/JobDispatcher.php`. Never run an arbitrary class name from a request. Adding a handler is a code change plus a test.
- Update `docs/api.md` when you change an endpoint, and the "What was verified / not verified" section of the README when a claim changes.
- Do not invent metrics, screenshots, or verification claims. If you did not run something, do not say you did.
- Never commit secrets: API keys, tokens, passwords, or real `.env` files. If you spot one in the history, report it privately through the process in `SECURITY.md` instead of opening a public issue.

## Pull request process

1. Open the PR against `main` with a clear title and a short description: what changed, why, and how you tested it.
2. Fill in the pull request template. List the exact commands you ran and their result.
3. Link any related issue (`Fixes #123`).
4. Expect review comments on scope and verification. A PR that adds an unverified feature claim will be asked to either verify it or remove the claim.
5. The maintainer merges. Do not force-push to `main`.

## Reporting bugs

Open an issue using the bug report template (`.github/ISSUE_TEMPLATE/bug_report.yml`): steps to reproduce, what you expected, what happened, the API response, and the relevant log lines if you have them. For security issues, do not use the public tracker; see `SECURITY.md`.

## Suggesting features

Open an issue using the feature request template (`.github/ISSUE_TEMPLATE/feature_request.yml`): the problem, the proposed change, and how it fits the roadmap in the README. Features that need real handler side effects, per-user auth, scheduled jobs, or autoscaling are on the roadmap already; a proposal should say how it would be verified against live Postgres and Redis.
