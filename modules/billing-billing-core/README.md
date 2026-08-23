# Billing Core

Framework-neutral primitives shared by Liberu billing capabilities.

This package provides deterministic minor-unit amounts and inclusive billing
periods. It deliberately has no dependency on the application `App\\` namespace,
so catalog, invoicing, payments, and presentation modules can depend on the
same core contract without coupling to one another.
