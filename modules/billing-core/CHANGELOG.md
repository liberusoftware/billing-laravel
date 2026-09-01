# Changelog

- Route Billing Core deletion through domain actions and emit after-commit deletion events.
- Added team-scoped currency conversion with cached rates and decimal-aware rounding.
- Added jurisdiction-aware inclusive and exclusive tax calculation.
- Added billing-account update and lifecycle transition actions with guarded API, Filament, and Livewire workflows.
- Added customer tax exemptions and threshold/tiered tax profile calculation.

## 0.1.0

- Added billing account persistence, lifecycle states, authorization policy,
  creation action, listing query, billing periods, and minor-unit amounts.
