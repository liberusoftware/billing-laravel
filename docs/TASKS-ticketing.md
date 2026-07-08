# TASKS — Support Ticketing Depth

Source spec: `docs/scope.md` → `## Integrated Support Tools` → Support ticketing bullet (line 95).
**Status: PARTIAL.** Basic ticketing exists — `app/Models/Ticket.php` (fillable `user_id`, `title`, `description`, `status`, `priority`), `TicketResponse` (`content`), `TicketController` (resource routes), `TicketPolicy`, Blade views under `resources/views/tickets/`. Missing the WHMCS help-desk depth: **departments, staff assignment, attachments, custom fields, escalation, email piping.**

## Branch & PR conventions
- Do this work on its own branch — create **`feature/ticketing-depth`** off `main` (in the `src/` repo) before starting; never commit to `main` directly.
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
- Extend the **existing** `Ticket`/`TicketResponse` models and `TicketController`; don't fork a parallel system.
- `Ticket` is keyed by `user_id` (the opener). New FKs are additive + nullable so existing tickets/tests keep passing.
- Models use the `#[Fillable([...])]` attribute (not `$fillable`) — match `Ticket.php`'s existing style. Add new fields to its `#[Fillable]` list and `@property` docblock.
- File uploads use **private** visibility; downloads gated by `TicketPolicy`.

---

## Tasks (priority order)

### P0 — Departments + assignment

- [x] **TK1. Ticket departments** (spec 95) — dep none
  - Migration `ticket_departments`: `id`, `team_id` (FK teams, cascade), `name`, `email` (string, nullable — for piping in TK6), `is_active` (boolean, default true), timestamps.
  - Migration: add `tickets.department_id` (nullable FK ticket_departments, nullOnDelete). `Ticket belongsTo Department`, add `'department_id'` to fillable.
  - Admin CRUD resource for departments; department select on the ticket form.
  - Gate test `test_ticket_can_belong_to_department`.

- [x] **TK2. Staff assignment** (spec 95) — dep none
  - Migration: add `tickets.assigned_to` (nullable FK users, nullOnDelete). `Ticket belongsTo assignee (User)`, scope `assignedTo($userId)`, `'assigned_to'` fillable.
  - Admin "Assign" action; show assignee in list. Optional event/notification on assignment.
  - Gate test `test_ticket_can_be_assigned_to_staff`; `test_assigned_scope_filters_by_staff`.

### P1 — Attachments + custom fields

- [x] **TK3. Attachments** (spec 95) — dep none
  - Migration `ticket_attachments`: `id`, polymorphic `attachable` (ticket OR ticket_response), `uploaded_by` (FK users), `path`, `original_name`, `mime`, `size`, timestamps. Private storage.
  - Add an upload field to the reply form; download route gated by `TicketPolicy` (requester is opener, assignee, or staff).
  - Gate tests: `test_attachment_stored_with_private_visibility`, `test_unauthorized_user_cannot_download_attachment` (403).

- [x] **TK4. Custom fields** (spec 95) — dep TK1
  - Migration `ticket_custom_fields`: `id`, `team_id`, `department_id` (nullable), `label`, `type` (`text|select|number|checkbox`), `options` (json, nullable), `is_required` (bool). Store values in `tickets.custom_fields` (json column on tickets).
  - Admin defines fields; ticket form renders them dynamically; required-validation enforced.
  - Gate tests: `test_custom_field_values_persist_on_ticket`, `test_required_custom_field_is_validated`.

### P2 — Escalation + email piping

- [x] **TK5. Escalation rules** (spec 95) — dep TK1, TK2
  - Migration `ticket_escalation_rules`: `id`, `team_id`, `department_id` (nullable), `minutes_without_response` (int), `action` (`raise_priority|reassign|notify`), `target_user_id` (nullable). 
  - `TicketEscalationService` + console command `tickets:escalate` (scheduled) that finds tickets breaching a rule and applies the action.
  - Gate tests: `test_overdue_ticket_raises_priority`, `test_escalation_reassigns_to_target`.

- [x] **TK6. Email piping (inbound)** (spec 95) — dep TK1 — DEPENDENCY-HEAVY
  - `InboundEmailService::handle(array $payload): TicketResponse|Ticket` — parse a normalized inbound-email payload (sender → match customer by email; subject/ticket-ref → append `TicketResponse` or open a new `Ticket` for the department whose `email` matched). Attachments → TK3.
  - Wire to a real mailbox (IMAP poll command OR a Mailgun/Postmark inbound webhook route) — **deploy-config, out of the gate test's scope**; the gate test feeds the service a parsed payload directly.
  - Gate tests: `test_inbound_email_appends_response_to_existing_ticket`, `test_inbound_email_opens_new_ticket_for_department`.

---

## Out of scope
- Live IMAP/mailbox polling infrastructure (TK6 ships the parse+create service; connecting a real inbox is deployment config + a provider choice).
- SLA dashboards / reporting analytics.
- Canned-response autosuggest already exists (`CannedResponse`) — not re-touched here.

## Critical path
TK1 + TK2 (independent) → TK4/TK5 (need departments/assignment). TK3 independent. TK6 last (heaviest).
