# TASKS — Domain Registrar Integrations

Source spec: `docs/scope.md` → `## Sell Domains (Domain Reselling)` (lines 28–53).
**Status: PARTIAL / stubs.** Plumbing exists but registrar API calls are skeletons: `app/Services/DomainService.php` orchestrates, `app/Services/Registrars/EnomClient.php` + `ResellerClubClient.php` return empty arrays / null. UI exists (`Filament/Client/Pages/DomainManagement.php`) but is non-functional because the clients don't call real APIs. Goal: make the registrar clients real so the already-wired domain features work.

## Branch & PR conventions
- Do this work on its own branch — create **`feature/domain-registrars`** off `main` (in the `src/` repo) before starting; never commit to `main` directly.
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
- Real HTTP via Laravel's `Http` facade so tests can `Http::fake()` — **no live API calls in tests.**
- Existing client methods to make real (signatures already present in `EnomClient`): `registerDomain`, `renewDomain`, `transferDomain`, `getAvailableTlds`, `getDomainPrice`, `getDnsRecords`, `addDnsRecord`, `deleteDnsRecord`, `getWhoisContacts`, `updateWhoisContacts`. `DomainService` wraps them per-`Subscription` (domain fields live on `Subscription`: `domain_name`, `domain_registrar`, `domain_expiration_date`).
- Credentials in `config/services.php` + `.env` (never hardcoded). Each task ships a `Http::fake()` gate test.

---

## Tasks (priority order)

### P0 — Interface + real Enom client

- [x] **R1. `RegistrarClient` interface + binding** — dep none
  - Extract `App\Services\Registrars\Contracts\RegistrarClient` interface (the 10 methods above). `EnomClient` + `ResellerClubClient` implement it.
  - `DomainService` resolves the client by registrar name (`enom`|`resellerclub`) via a small factory/match — replaces the two injected concretes.
  - Gate test `test_domain_service_resolves_registrar_by_name` (returns the right client class per name).

- [x] **R2. Real Enom HTTP client — register + availability + price** (spec 32, 33) — dep R1
  - Implement `registerDomain`, `getDomainPrice`, and a `checkAvailability(string $domain): bool` against the Enom reseller API using `Http::withQueryParameters(...)`. Parse the response; throw a typed exception on API error.
  - Gate tests (`Http::fake()`): `test_register_calls_enom_with_credentials`, `test_availability_parses_available_response`, `test_api_error_throws`.

### P1 — Availability search, transfers, renewals, sync

- [x] **R3. Public domain search + availability** (spec 39, 40) — dep R2
  - Public route + page: customer enters a domain → `checkAvailability` across enabled TLDs; show available + price (from `Tld` markup). Optional: simple namespinning suggestions (append common TLDs / hyphenations).
  - Gate test `test_domain_search_returns_availability_and_price`.

- [x] **R4. Transfer flow** (spec 34) — dep R2
  - `transferDomain` with EPP/auth code: initiate + persist transfer status on the subscription. `DomainService::transferDomain` end-to-end.
  - Gate tests (`Http::fake()`): `test_transfer_initiates_with_auth_code`, `test_transfer_status_persists`.

- [x] **R5. Auto-renew on payment** (spec 35) — dep R2
  - On a paid invoice for a domain subscription, trigger `DomainService::renewDomain` (listener on the invoice-paid event/`Payment` created). Idempotent.
  - Gate test `test_paid_domain_invoice_triggers_registry_renewal` (fake the registrar; assert renew called + expiration advanced).

- [x] **R6. Domain sync command** (spec 36) — dep R2
  - Extend/replace `SyncEnomDomains` to sync per-domain **due dates, status, transfer-away detection** (not just TLD prices). Scheduled daily.
  - Gate test `test_sync_updates_expiration_and_status`.

### P2 — DNS/WHOIS real, value-adds, second registrar

- [x] **R7. Real DNS + WHOIS** (spec 47, 48) — dep R2
  - Implement `getDnsRecords`/`addDnsRecord`/`deleteDnsRecord` and `getWhoisContacts`/`updateWhoisContacts` against the API. The `DomainManagement` client page becomes functional.
  - Gate tests (`Http::fake()`): `test_dns_records_fetched`, `test_dns_record_added`, `test_whois_contacts_updated`.

- [x] **R8. ID protection + free-domain bundling** (spec 49, 51) — dep R2
  - WHOIS-privacy add-on toggle (product/flag on the domain subscription); free-domain-with-hosting rule at order time.
  - Gate tests: `test_id_protection_flag_persists`, `test_free_domain_applied_with_hosting`.

- [x] **R9. Real ResellerClub client** — dep R1, R2
  - Mirror R2/R4/R7 for `ResellerClubClient` against the ResellerClub API.
  - Gate tests (`Http::fake()`): `test_resellerclub_register_calls_api`.

---

## Out of scope
- New registrars: **CentralNic, OpenSRS, Nominet** (spec 53) — each is a separate client + plan; do after Enom/ResellerClub are solid.
- Premium-domain auctions / aftermarket (spec 50 beyond markup tiers, which already exist via `Tld`).
- Spotlight TLDs marketing display (spec 43) — cosmetic, defer.

## Critical path
R1 → R2 → (R3, R4, R5, R6 parallel) → R7 → R8/R9. R2 unlocks ~8 already-stubbed domain features — highest leverage.
