# Billing module conformance matrix

This document is the repository-local implementation map for the billing scope
described by Wayfinder issues #607, #609–#624, #626, and #630. The issue bodies
and the Liberu documentation repository remain authoritative for domain
behavior; this file records where each presentation surface lives and how it is
verified.

## Package boundary

Each capability has one provider-neutral domain package and optional one-to-one
API, Filament 5, and Livewire 4 adapters. Domain actions, queries, policies,
events, persistence, and invariants stay in the domain package. Adapters
validate and authorize at their boundary, then delegate to those public
operations. Billing records are team-scoped unless the domain explicitly
allows a global catalog/configuration record.

| Capability | Domain | API | Filament | Livewire | Default route prefix |
| --- | --- | --- | --- | --- | --- |
| Billing Core | `billing-billing-core` | `billing-core-api` | `billing-core-filament` | `billing-core-livewire` | `/api/v1/billing/billing-core` |
| Catalog | `billing-catalog` | `billing-catalog-api` | `billing-catalog-filament` | `billing-catalog-livewire` | `/api/v1/billing/catalog` |
| Pricing | `billing-pricing` | `billing-pricing-api` | `billing-pricing-filament` | `billing-pricing-livewire` | `/api/v1/billing/pricing` |
| Orders | `billing-orders` | `billing-orders-api` | `billing-orders-filament` | `billing-orders-livewire` | `/api/v1/billing/orders` |
| Subscriptions | `module-billing-subscriptions` | `module-billing-subscriptions-api` | `module-billing-subscriptions-filament` | `module-billing-subscriptions-livewire` | `/api/v1/billing/subscriptions` |
| Invoicing | `module-billing-invoicing` | `module-billing-invoicing-api` | `module-billing-invoicing-filament` | `module-billing-invoicing-livewire` | `/api/v1/billing/invoicing` |
| Payments | `module-billing-payments` | `module-billing-payments-api` | `module-billing-payments-filament` | `module-billing-payments-livewire` | `/api/v1/billing/payments` |
| Customer Portal | `module-billing-customer-portal` | `module-billing-customer-portal-api` | `module-billing-customer-portal-filament` | `module-billing-customer-portal-livewire` | `/api/v1/billing/customer-portal` |
| Provisioning | `module-billing-provisioning` | `module-billing-provisioning-api` | `module-billing-provisioning-filament` | `module-billing-provisioning-livewire` | `/api/v1/billing/provisioning` |
| Hosting | `module-billing-hosting` | `module-billing-hosting-api` | `module-billing-hosting-filament` | `module-billing-hosting-livewire` | `/api/v1/billing/hosting` |
| Domains | `module-billing-domains` | `module-billing-domains-api` | `module-billing-domains-filament` | `module-billing-domains-livewire` | `/api/v1/billing/domains` |
| ISP | `module-billing-isp` | `module-billing-isp-api` | `module-billing-isp-filament` | `module-billing-isp-livewire` | `/api/v1/billing/isp` |
| Communications | `module-billing-communications` | `module-billing-communications-api` | `module-billing-communications-filament` | `module-billing-communications-livewire` | `/api/v1/billing/communications` |
| Collections | `module-billing-collections` | `module-billing-collections-api` | `module-billing-collections-filament` | `module-billing-collections-livewire` | `/api/v1/billing/collections` |
| Usage | `module-billing-usage` | `module-billing-usage-api` | `module-billing-usage-filament` | `module-billing-usage-livewire` | `/api/v1/billing/usage` |
| Reporting | `module-billing-reporting` | `module-billing-reporting-api` | `module-billing-reporting-filament` | `module-billing-reporting-livewire` | `/api/v1/billing/reporting` |

## Capability coverage

| Capability | Required domain features |
| --- | --- |
| Billing Core | accounts; contacts; currencies; tax profiles; sequences; terms; billing settings |
| Catalog | products; plans; add-ons; bundles; configurable options; eligibility; channels; lifecycle |
| Pricing | recurring, one-time, usage, and tiered pricing; contracts; discounts; proration; snapshots |
| Orders | quotes; carts; checkout; fraud review; agreements; order state; change orders |
| Subscriptions | activation; renewal; trial; upgrade/downgrade; pause; cancellation; entitlement state |
| Invoicing | schedules; line generation; tax; credits; adjustments; PDFs; delivery; finalization |
| Payments | methods; mandates; gateway drivers; capture; allocation; refunds; disputes; reconciliation |
| Customer Portal | profile; orders; services; usage; invoices; payments; tickets; changes; cancellation |
| Provisioning | service state machine; provider drivers; queued operations; rollback; polling; reconciliation |
| Hosting | hosting accounts; plans; control-panel adapters; SSL; resources; lifecycle operations |
| Domains | search; contacts; registration; transfer; renewal; redemption; EPP; DNS; registrar adapters |
| ISP | access services; coverage; installation; RADIUS; usage; equipment; network adapters |
| Communications | VoIP/SMS service inventory; number lifecycle; usage/rating imports; provider adapters |
| Collections | retries; dunning; reminders; promises; credit control; suspension; write-off; recovery |
| Usage | meter definitions; ingestion; deduplication; aggregation; rating; corrections; thresholds |
| Reporting | MRR/ARR; churn; aging; revenue; tax; usage; provisioning; collection; provider metrics |

## Release verification

The following checks are required before merging a billing implementation wave:

```text
vendor/bin/pint --test
php -d memory_limit=1G vendor/bin/phpstan analyse --no-progress
php artisan module:validate
vendor/bin/pest --no-coverage --compact
git diff --check
```

API packages publish an OpenAPI 3.1 document under `openapi/v1/`, use Sanctum
authentication plus module abilities, apply idempotency middleware to writes,
and expose purpose-built JSON responses. Protected reads and writes must be
authorized before the operation and must fail closed when a required team is
missing. Filament plugins use stable package identifiers and package-local
resource discovery. Livewire aliases are package-qualified and public state is
validated and authorized on every mutation.

The issue tracker is intentionally not used as a completion flag: issues
#609–#624, #626, and #630 remain open while implementation and independent
verification continue.

## Provider adapters

Provider-neutral Payments remains independent of SDKs. Stripe and Paddle are
optional adapters registered through the gateway contract; Paddle support is
implemented in `module-billing-paddle` for issue #630. Installing an adapter,
enabling its module, configuring credentials, and granting payment capability
are separate deployment decisions.
