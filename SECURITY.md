# Security Policy

## Supported versions

| Version | Supported |
| --- | --- |
| 0.1.x | Yes |

Only the latest release on `main` receives fixes. v0.1.0 is the only release so far.

## Reporting a vulnerability

Do not open a public issue for a security vulnerability.

Report it by opening a GitHub issue in this repository marked as a security report, or by contacting the maintainer through the project website at https://mrpythonist.com. Include:

- A description of the vulnerability and where it is (file and, if possible, line).
- Steps to reproduce, or a minimal proof of concept.
- What an attacker could do with it, in plain terms.
- Whether you believe it is already being exploited, if you know.

You will get an acknowledgement, and the maintainer will work on a fix. Please allow reasonable time for a fix and a release before disclosing the issue publicly.

## What to look for

This project is a good place to check for:

- Handler execution outside the allowlist in `backend/app/Services/JobDispatcher.php`. A request must never be able to name an arbitrary class and have it run.
- Authentication bypass on write endpoints. When `JOB_MANAGER_API_KEY` is set, `POST`, `PATCH`, and `DELETE` endpoints must require the `X-API-Key` header.
- Injection through job payloads, queue names, or search filters that reach the database.
- Secrets committed to the repo, especially real `.env` files, API keys, tokens, or passwords.

## Known limitations (not vulnerabilities)

- The API key is a single shared key for write endpoints. It is not per-user auth. That is a documented v0.1.0 limitation, not a bug. See `docs/DECISIONS.md`.
- Handlers are simulated in v0.1.0 and do nothing real. That is by design.
- The Compose setup serves plain HTTP on port 8000. Put the API behind TLS and a reverse proxy before exposing it beyond local development.
- `APP_DEBUG` defaults to `true` in the example env file. Set it to `false` outside local development.
