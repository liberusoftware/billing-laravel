# Billing Stripe

Stripe adapter for the provider-neutral `liberusoftware/module-billing-payments` contract.

Install `liberusoftware/billing-stripe`, enable `module-billing-stripe`, and configure `STRIPE_SECRET`. The adapter registers `stripe` and `Stripe` drivers with `GatewayManager`, captures with Stripe PaymentIntents, and refunds by PaymentIntent reference.
