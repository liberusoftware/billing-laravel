# TASKS — Software Licensing Addon

Source spec: `docs/scope.md` → `## Addon: Software Licensing` (lines 179–192).
**Status: NOT IMPLEMENTED** — no models, services, migrations, resources, or dependencies exist. Fully greenfield.

## Branch & PR conventions
- Do this work on its own branch — create **`feature/software-licensing`** off `main` (in the `src/` repo) before starting; never commit to `main` directly.
- PRs raised from this plan: add label **`enhancement`** and request review from **`curtisdelicata`**.

---

## Development Workflow — Laravel TDD Cycle

All feature and bugfix work follows test-driven development. Every task in this
file is "done" only when its gate test exists and passes.

**Cycle:** RED → Verify RED → GREEN → Verify GREEN → REFACTOR → Repeat.

1. **RED — write failing test.** One minimal test showing what the Laravel feature should do.
2. **Verify RED — watch it fail.** `php artisan test --filter=test_name`. Confirm it fails for the right reason.
3. **GREEN — minimal Laravel code.** Simplest code that passes the test. No more.
4. **Verify GREEN — re-run the filter.** Confirm the test now passes.
5. **REFACTOR — clean up, only after green:**
   - Extract **services** for complex logic (`app/Services/*Service.php`).
   - Create **policies** for authorization.
   - Add **query scopes** for reusable query logic.
   - Use **events** for side effects.
6. **Repeat** for the next behavior.

Test env: sqlite in-memory (`phpunit.xml`) — no DB setup. Most tests are feature tests;
use factories + custom states. Run the minimal filter while iterating; run the full suite
before finalizing.

---

## Build conventions
- Build as **core** (`app/Models` + `app/Filament/{Admin,Client}` + `app/Services`), matching every other domain — not the module system.
- Billing party is **`Customer`** (`customers.user_id` → User `hasOne` Customer). Client-panel resources scope by `auth()->user()->email` matching the customer email (see `Filament/Client/Resources/InvoiceResource`).
- Models use the `#[Fillable([...])]` attribute (not `$fillable`), `declare(strict_types=1)`, `@property` docblock before the attribute, `casts()` method, typed relations. Every model gets a factory.
- Public SDK/validation endpoints go under `routes/api.php` (Sanctum-exempt where the SDK calls anonymously with a license key).

---

## Tasks (priority order)

### P0 — Core domain + remote validation

- [x] **L1. `License` model + key generation** (spec 186)
  - Migration `licenses`: `id`, `team_id` (FK teams, cascade), `customer_id` (FK customers, cascade), `product_service_id` (FK products_services, nullable, nullOnDelete), `license_key` (string, unique), `status` (string, default `active` — `active|suspended|reissue_pending|expired`), `max_instances` (unsignedInteger, default 1), `valid_until` (date, nullable), `notes` (text, nullable), timestamps, softDeletes.
  - `LicenseService::generate(string $prefix = 'LIC', int $segments = 4, int $segmentLength = 5): string` — prefix + grouped random alphanumeric; guarantee uniqueness against the table.
  - Model: `belongsTo(Customer/Team/ProductsService)`, `hasMany(LicenseInstance)`; scopes `active()`.
  - Gate test `test_generating_license_creates_unique_prefixed_key`: assert key starts with prefix, is unique, persists.

- [x] **L2. Remote validation endpoint** (spec 183, 185, 192) — dep L1
  - Public route `POST /api/v1/license/validate` (no Sanctum) → `LicenseValidationController`. Input: `license_key`, instance identifier (`domain`/`ip`/`instance_id`). Returns `{valid: bool, status, data}`.
  - `LicenseService::validate(string $key, array $instance): array` — invalid if key missing/suspended/expired; if active, register/refresh the calling instance (L3) and enforce `max_instances`.
  - Gate tests: `test_valid_key_returns_active`, `test_suspended_key_returns_invalid`, `test_exceeding_max_instances_is_rejected`.

### P1 — Instances, activation, reissue, abuse

- [x] **L3. `LicenseInstance` tracking (local + remote keys)** (spec 183, 185) — dep L2
  - Migration `license_instances`: `id`, `license_id` (FK, cascade), `identifier` (string — domain/ip/install id), `ip_address` (string, nullable), `last_validated_at` (timestamp), `local_key` (string, nullable — signed offline token so access survives platform downtime), timestamps. Unique (`license_id`,`identifier`).
  - On validate: upsert instance, stamp `last_validated_at`, issue an HMAC-signed `local_key` (TTL) the SDK caches for offline checks.
  - Gate tests: `test_activation_records_instance`, `test_local_key_verifies_offline`.

- [x] **L4. Reissue + abuse detection** (spec 189, 190) — dep L3
  - `LicenseService::reissue(License $license): void` — clears instances, sets status back to `active`, ready for a new install.
  - Abuse guard: block reissue if > N reissues within a window (config). Throw on excess.
  - Gate tests: `test_reissue_clears_instances`, `test_excessive_reissue_is_blocked`.

### P2 — UI + downloads + SDK

- [x] **L5. Admin `LicenseResource`** (spec — admin management) — dep L1
  - Admin CRUD; row actions **Reissue** and **Suspend/Unsuspend**. Customer + product selects.
  - Gate test `test_admin_can_issue_license`.

- [x] **L6. Client portal license list** (spec 184, 191) — dep L1
  - Client `LicenseResource` (read + reissue + view key + download), email-scoped via `getEloquentQuery`.
  - Gate tests: `test_client_sees_only_own_licenses`, `test_client_can_reissue_own_license`.

- [x] **L7. Download restrictions** (spec 187) — dep L2
  - A download route/policy that serves a protected file only when the requesting license validates (active + within support/validity). Reuse `LicenseService::validate`.
  - Gate test `test_download_denied_without_valid_license` (assert 403), `test_download_allowed_with_valid_license`.

- [x] **L8. Copy-paste PHP SDK sample** (spec 182) — dep L2, L3
  - Ship a documented `resources/sdk/license-client.php` sample that calls the validate endpoint and caches the local key. Doc artifact — no automated test (verify by example).

---

## Out of scope
- Non-PHP SDK samples (spec 192 "flexible API") — the endpoint is language-agnostic; only the PHP sample ships. Other-language samples are docs, deferred.
- Hardened key cryptography beyond HMAC signing (HSM, asymmetric licensing) — note as a future ceiling.
- Marketplace/store listing of licensed products — separate from licensing mechanics.

## Critical path
L1 → L2 → L3 → L4. UI (L5/L6) parallel after L1. L7/L8 after L2/L3.
