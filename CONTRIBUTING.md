# Contributing

Thanks for your interest in Background Job Manager.

## Getting started

1. Fork the repo and clone your fork.
2. Copy `backend/.env.example` to `backend/.env` and `frontend/.env.example` to `frontend/.env`.
3. Start the stack with `docker compose up --build` (or run the API and frontend separately, see README).
4. Run the tests before opening a pull request.

## Rules

- Keep the code typed and readable. PHP 8.2+ syntax, TypeScript strict mode.
- Do not add a dependency without a reason in the PR description.
- Do not claim a feature works end to end unless you ran it against real Postgres and Redis.
- Job handlers must stay in the allowlisted registry in `JobDispatcher`. Never run an arbitrary class name from a request.
- Update `docs/API.md` when you change an endpoint, and the README verified/not-verified section when a claim changes.
- One logical change per PR. Small PRs get reviewed faster.

## Reporting bugs

Open an issue with steps to reproduce, what you expected, and what happened. Include the API response and the relevant log lines if you have them.
