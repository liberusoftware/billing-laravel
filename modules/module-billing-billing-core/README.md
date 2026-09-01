# Billing Core

Framework-neutral primitives shared by Liberu billing capabilities.

The billing core owns tenant-scoped accounts, contacts, currencies, tax
profiles, numbering sequences, payment terms, and billing settings. Domain
actions and policies are exposed through the core API, Filament resources, and
Livewire components.

This package provides deterministic minor-unit amounts and inclusive billing
periods. It deliberately has no dependency on the application `App\\` namespace,
so catalog, invoicing, payments, and presentation modules can depend on the
same core contract without coupling to one another.
