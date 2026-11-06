# Support

## Getting help

- Start with the README: installation, configuration, quick start, and the API reference are all there.
- For API detail, see `docs/api.md`. For how the pieces fit together, see `docs/architecture.md`. For local development, see `docs/development.md`.
- Check the open issues in this repository. Your question may already be answered there.

## Asking a question

Open a GitHub issue in this repository and include:

- What you were trying to do.
- What you ran, and the exact command or request.
- What happened, including the full error message or API response.
- Your setup: Docker Compose or manual install, PHP version, Node.js version, and whether Postgres and Redis were running.

Vague reports ("it does not work") are hard to answer. The more specific the report, the faster the help.

## Bugs and feature requests

- Bug report: use the bug report template (`.github/ISSUE_TEMPLATE/bug_report.yml`).
- Feature request: use the feature request template (`.github/ISSUE_TEMPLATE/feature_request.yml`).
- Security issue: do not use the public tracker. See `SECURITY.md`.

## What support covers

- Help getting the project running with Docker Compose or a manual install.
- Questions about the API, the dashboard, queues, retries, and worker heartbeats.
- Bug reports for behavior that differs from the documentation.

## What support does not cover

- Writing or debugging your own job handlers. The registry interface is documented in `docs/architecture.md`; real handler implementations are your code.
- Running this in production. v0.1.0 is a developer tool; the README "Deployment" section lists what to change before exposing it beyond local development.
- General Laravel, Postgres, Redis, or React questions. The official docs for those projects are the right place.
