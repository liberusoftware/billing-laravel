# Liberu Billing Orders Filament

Filament 5 adapter for the billing-orders domain module.

OrdersFilamentPlugin registers the order resource on the admin panel.
Creation delegates to the core CreateOrder action; the resource does not
duplicate order invariants.
