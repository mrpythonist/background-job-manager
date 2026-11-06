## What changed

## Why

## How I tested it

List the exact commands you ran and the result of each. For example:

- `php backend/scripts/verify.php` - passed
- `php backend/scripts/smoke.php` - passed
- `cd backend && composer install && php artisan test` - passed / not run (state why)
- `cd frontend && npm install && npm run build` - passed
- Live run against Postgres and Redis - yes / no (if yes, describe what you ran)

Do not claim an end-to-end run unless you actually ran one against live Postgres and Redis.

## Checklist

- [ ] One logical change in this PR
- [ ] `docs/api.md` updated, if an endpoint changed
- [ ] README "What was verified / not verified" updated, if a claim changed
- [ ] No secrets, `.env` files, `vendor/`, `node_modules/`, or `dist/` committed
- [ ] No new dependency without a reason in the description above
