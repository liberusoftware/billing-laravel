# TASKS.md

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

## 🔴 CRITICAL — PRIORITY

### Missing: Project Management Addon
Source spec: `src/docs/scope.md` → `## Addon: Project Management` (lines 145–160).

**Status: NOT IMPLEMENTED.** No `Project`/`Task`/`TimeEntry` models, migrations, or module in `src/app/`. Only `BlogModule` exists. Entire addon absent from codebase.

**Build as:** core feature in `src/app/Models` + `src/app/Filament/{Admin,Client}` (matches every other domain; see Build conventions below — NOT the module system).

**Dependencies in codebase (verified present):**
- Billing party is **`Customer`** (`invoices.customer_id` → `customers`; `Customer::invoices()`). Use `Customer`, NOT `Client` — projects belong to a Customer.
- `Invoice` + `Invoice_Item` (snake class, factory `InvoiceItemFactory`) + `Payment` — for time-to-invoice + invoice history.
- `Ticket` / `TicketResponse` — ticketing exists. NOTE: `Ticket` is keyed by `user_id`, not `customer_id` — see T12.
- Three Filament panels: **Admin** (`app/Filament/Admin`, staff), **App** (`app/Filament/App`, business owner), **Client** (`app/Filament/Client`, end-customer self-service). Resources are per-panel, not shared.
- `team_id` scoping: all tenant-owned tables carry `team_id` (Jetstream tenancy). Match sibling migrations.

**Build conventions:** build as **core** (not a module) — every other domain (Invoice, Customer, Currency) lives in `app/Models` + `app/Filament/{Admin,Client}`; only `BlogModule` uses the module system, which adds ServiceProvider wiring for Filament discovery no other feature pays. Models → `app/Models`, migrations → `database/migrations`, services → `app/Services`, resources → `app/Filament/<Panel>/Resources`. Generate via `php artisan make:model`/`make:migration`/Filament generators (`--no-interaction`). Every model gets a factory + seeder. Each task ships its PHPUnit gate test (feature test unless noted) per the TDD cycle above.

#### Tasks (priority order)

**P0 — Core domain (blocks all)**

- [x] **T1. `Project` model** (spec 148) ✅ built as core; Admin CRUD + Client read-only (email-scoped); 2 tests green
  - Migration `projects`: `id`, `team_id` (FK teams, cascade), `customer_id` (FK customers, cascade), `name` (string), `description` (text, nullable), `status` (string, default `open` — enum-backed: `open|in_progress|on_hold|completed|cancelled`), `due_date` (date, nullable), `created_by` (FK users — staff opener), timestamps, softDeletes.
  - Model: `belongsTo(Customer)`, `belongsTo(Team)`, `hasMany(Task)`, `hasMany(ProjectFile)`, `hasMany(ProjectNote)`, `morphMany` invoices link via T5. Status cast to enum. `team_id` auto-scoped (match sibling tenancy trait).
  - Filament: **Admin** `ProjectResource` (full CRUD, list + form). **Client** `ProjectResource` read-only, scoped to `auth()->user()` customer.
  - Gate test `test_admin_can_create_project_for_customer`: create via `ProjectResource\CreateProject`, assert `assertDatabaseHas('projects', [...])` + `customer_id` set.

- [x] **T2. `Task` model** (spec 150–151) — dep T1
  - Migration `tasks`: `id`, `project_id` (FK projects, cascade), `title` (string), `description` (text, nullable), `is_complete` (boolean, default false), `completed_at` (timestamp, nullable), `due_date` (date, nullable), `priority` (string, default `medium` — `low|medium|high`), `assigned_to` (FK users, nullable — staff), `sort_order` (int, default 0), timestamps.
  - Model: `belongsTo(Project)`, `belongsTo(User, 'assigned_to')`, `hasMany(TimeEntry)`. Scopes: `outstanding()` = `where('is_complete', false)`, `completed()`. Cast `is_complete` bool, `priority` enum.
  - Filament: Admin — `RelationManager` on `ProjectResource` (task list inline), mark-complete action sets `is_complete` + `completed_at`.
  - Gate test `test_marking_task_complete_sets_completed_at`: complete a task, assert `is_complete` true + `completed_at` not null; assert `Task::outstanding()` excludes it.

- [x] **T3. `TimeEntry` model + timer** (spec 149) — dep T2
  - Migration `time_entries`: `id`, `task_id` (FK tasks, cascade), `user_id` (FK users — admin who logged), `started_at` (timestamp), `ended_at` (timestamp, nullable — null = running), `duration_seconds` (int, default 0), `is_billable` (boolean, default true), `rate` (decimal 10,2, nullable — billing rate override), `invoiced_at` (timestamp, nullable — set by T4), `notes` (text, nullable), timestamps.
  - Model: `belongsTo(Task)`, `belongsTo(User)`. Scopes: `running()` = `whereNull('ended_at')`, `billable()`, `uninvoiced()` = `whereNull('invoiced_at')`. On stop: compute `duration_seconds = ended_at - started_at`.
  - Service `TimeTrackingService`: `start(Task, User)` (guard: one running entry per user — stop existing first), `stop(TimeEntry)`.
  - Filament: Admin task RelationManager — Start/Stop timer actions; manual add-time form.
  - Gate test `test_stopping_timer_records_duration`: start then stop, assert `ended_at` set + `duration_seconds > 0`; `test_user_cannot_have_two_running_timers`.

**P1 — Billing integration**

- [x] **T4. Time-to-invoice** (spec 152) — dep T3
  - Service `ProjectBillingService::invoiceTime(Project, array $timeEntryIds): Invoice`: gather billable + uninvoiced entries → `Invoice_Item` rows via `$invoice->items()->createMany()`. **Actual item columns:** `description`, `quantity` (hours), `unit_price` (entry `rate` or project default), `total_price` (= qty × unit_price), `currency`, `product_service_id` (**nullable — leave null** for time). Compute `Invoice.total_amount` = sum(total_price) and set at create (no auto-recalc hook exists). Create `Invoice` with `customer_id` (=project->customer_id), `issue_date`, `due_date`, `total_amount`, `currency`, `status='pending'`, `team_id`. Stamp each entry `invoiced_at = now()` in same DB transaction (idempotent — no double-bill).
  - **RESOLVED:** `invoice_items.product_service_id` is nullable (`2024_06_18_000000_update_invoice_items_for_api`). Pattern ref: `Api/InvoiceController::store()` lines 78–123.
  - Filament: Admin `ProjectResource` action "Invoice logged time" → preview selectable entries → generate.
  - Gate test `test_invoicing_time_creates_invoice_and_stamps_entries`: assert invoice created with correct total, entries `invoiced_at` set; `test_already_invoiced_time_is_excluded` (re-run invoices nothing).

- [x] **T5. Project ↔ invoice link + history** (spec 156) — dep T1, T4
  - Migration: add `invoices.project_id` (nullable FK projects, nullOnDelete). Model: `Invoice belongsTo Project`, `Project hasMany Invoices`.
  - Filament: Admin `ProjectResource` — Invoices RelationManager showing invoice #, total, **payment status** (reuse `Invoice` status / `Payment` relation). Client panel mirrors read-only.
  - Gate test `test_project_lists_its_invoices_with_payment_status`.

**P2 — Collaboration**

- [x] **T6. File sharing** (spec 155, 160) — dep T1
  - Migration `project_files`: `id`, `project_id` (FK, cascade), `uploaded_by` (FK users), `path` (string), `original_name` (string), `mime` (string), `size` (int), `customer_visible` (boolean, default **false**), timestamps.
  - Model: `belongsTo(Project)`, `belongsTo(User, 'uploaded_by')`. Scope `customerVisible()`.
  - **Security:** Filament `FileUpload` `->visibility('private')` (never public). Client panel lists ONLY `customer_visible = true` files; downloads authorized via policy (file's project belongs to auth customer).
  - Gate test `test_client_sees_only_customer_visible_files`; `test_client_cannot_download_staff_only_file` (assert 403).

- [x] **T7. Staff messageboard** (spec 154) — dep T1
  - Migration `project_notes`: `id`, `project_id` (FK, cascade), `user_id` (FK users — staff author), `body` (text), timestamps.
  - Model: `belongsTo(Project)`, `belongsTo(User)`. Staff-only — **no Client panel resource, no API exposure**.
  - Filament: Admin RelationManager only.
  - Gate test `test_staff_notes_not_visible_on_client_panel` (assert Client ProjectResource has no notes relation / 403 on direct access).

- [x] **T8. Discussions / client comms** (spec 148, 160) — dep T1
  - Migration `project_messages`: `id`, `project_id` (FK, cascade), `author_id` (FK users), `author_type` (string: `staff|customer`), `body` (text), timestamps.
  - Model: `belongsTo(Project)`, `belongsTo(User, 'author_id')`. Visible on BOTH Admin + Client panels (client-visible thread).
  - Filament: Admin + Client RelationManagers; Client can post (creates `author_type = customer`).
  - Gate test `test_customer_can_post_message_to_their_project`; `test_customer_cannot_post_to_others_project` (403).

**P3 — Visibility**

- [x] **T9. Due date + priority surfacing** (spec 151) — dep T1, T2
  - No new tables — query/UI on T1/T2 fields. Scopes `Project::overdue()` (`due_date < today` & not completed), `Task::dueWithin($days)`. Admin table: sort by `due_date`, colour/badge by priority + days-remaining.
  - Gate test `test_overdue_scope_returns_only_past_due_open_projects`.

- [x] **T10. Reporting** (spec 157) — dep T1–T3
  - Service `ProjectReportService`: totals — project count by status, total time worked (sum `duration_seconds`) per project/per staff, billable vs non-billable hours.
  - Filament: Admin Widgets (stat cards + table) on a Projects dashboard page.
  - Gate test (unit) `test_report_aggregates_time_worked_per_project`.

- [x] **T11. Audit log** (spec 158) — dep T1–T3
  - **RESOLVED:** no spatie. App has its own audit system — `AuditLog` (polymorphic `MorphTo auditable`, JSON `old_values`/`new_values`, `user_id`, `event`, `ip_address`) + `AuditLogService::log()`. Add an Observer on Project/Task/TimeEntry calling `AuditLogService::log()` on created/updated/deleted. No new dep, no new table.
  - Gate test `test_updating_project_records_activity_entry`.

**P4 — Ticketing link**

- [x] **T12. Ticket ↔ project** (spec 153) — dep T1
  - Migration: add `tickets.project_id` (nullable FK projects, nullOnDelete). Model: `Ticket belongsTo Project`, `Project hasMany Tickets`.
  - **RESOLVED:** `tickets.user_id` → resolve customer via `$ticket->user->customer` (User `hasOne` Customer; `customers.user_id` nullable). If `customer` is null, action must prompt for manual customer selection.
  - Filament: Admin `TicketResource` — "Create project from ticket" action (prefills name from subject, customer from resolved mapping) + a "Link to existing project" select.
  - Gate test `test_create_project_from_ticket_links_both`; `test_ticket_without_resolvable_customer_requires_manual_selection`.

**Critical path:** T1 → T2 → T3 → T4 → T5. Collaboration (T6–T8) parallel after T1. T9–T12 after their deps.

#### Out of scope
- Multi-language client UI (spec 159) — platform-wide i18n, not addon-local.
- Realtime chat/websockets for discussions — basic threads suffice (Reverb available; YAGNI until asked).
