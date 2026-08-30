# Changelog

## Unreleased

- Emit after-commit events for invoice creation, line changes, finalization, delivery, adjustments, and late fees.
- Add the `partially_paid` invoice lifecycle state used by modular payment allocation.

## 0.1.0

- Add the provider-neutral invoicing boundary and lifecycle actions.
- Recalculate mixed-rate invoice taxes correctly and enforce team ownership for invoice support records.
- Keep invoice reads available to read-scoped tokens while reserving mutations for write-scoped tokens.
- Add validated recurring invoice schedule creation and execution actions.
- Add invoice credit/adjustment, document generation, and delivery actions.
- Render actual PDF bytes with Dompdf while retaining the HTML source payload.
