# Changelog

## 0.1.0

- Enforce gateway, dispute-reason, and reconciliation-reference invariants in payment lifecycle actions.

- Add the independent provider-neutral Payments capability.
## Unreleased

- Emit an after-commit `PaymentAllocated` domain event for allocation integrations.
- Emit after-commit events for captured, refunded, disputed, and reconciled payments.
- Add active/inactive payment-method and pending/active/revoked/expired mandate lifecycle transitions.
- Enforce invoice remaining balances for allocations and mark fully allocated invoices paid.
- Gateway capture failures now transition pending payments to failed and emit a failure event.
- Gateway refund failures now persist a failed refund attempt and emit a failure event.
