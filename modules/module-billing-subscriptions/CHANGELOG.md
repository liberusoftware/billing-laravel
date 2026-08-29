# Changelog

## Unreleased

- Preserve configurable subscription period lengths during activation and renewal.
- Add an idempotent expiry action for due non-renewing subscriptions and expose it through API, Filament, and Livewire.

## 0.1.0

- Add the independent Billing Subscriptions lifecycle boundary.
- Emit domain events for plan changes, pauses, resumes, cancellations, renewals,
  activation, and entitlement updates.
- Preserve the legacy subscription ID-protection value through the domain,
  migration, and API resource boundaries.
