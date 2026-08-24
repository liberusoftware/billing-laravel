# Liberu Billing Subscriptions

Provider-neutral subscription lifecycle for activation, renewal, trials,
plan changes, pause, cancellation, and entitlement state.

The package owns the billing_subscriptions table and never depends on
application classes or a payment/provider SDK. Presentation adapters consume
its actions, queries, policies, and events.
