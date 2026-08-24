# Billing module runbook

This runbook is for operators and maintainers of the billing modules listed in Wayfinder issue #626.

## Verify a deployment

    composer validate --no-check-publish
    composer install --no-interaction --prefer-dist
    php artisan migrate --force
    php artisan package:discover --ansi
    php artisan route:list --path=api/v1/billing

Publish API contracts with the corresponding `*-openapi` tag for each module API package.

## Diagnose a failed operation

1. Check the module application log and queue or worker log.
2. Confirm the request has the required `billing.<module>.read` or `billing.<module>.write` ability and the active team is correct.
3. For mutating API requests, retry with the original `Idempotency-Key`. A completed response is replayed; a different request with the same key is rejected.
4. For provisioning, inspect operation status, error, attempt count, and `next_poll_at` before retrying the provider operation.

Provider adapters must be registered through the module registry. Core modules must not require provider SDKs; install and configure adapters in the application or integration package.

## Rollback

Stop workers before rolling back a release that changes operation payloads or database columns. Take a database snapshot first, deploy the previous application version, and run only the explicitly documented down migration. Never delete tenant records to recover from a failed billing operation.

## Contract and compatibility checks

Run focused module coverage tests before a release, then run the full suite. Keep API paths, operation IDs, error responses, and pagination metadata backward compatible; document deprecations for one release cycle before removal.
