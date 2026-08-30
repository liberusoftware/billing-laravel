# Changelog

- Emit after-commit events for communication providers, services, VoIP accounts, numbers, usage imports, and CDR ingestion.
- Extract call-rate rule creation into the Communications domain action boundary.
- Added provider-neutral voice account provisioning, CDR rating, idempotency, and fraud alerts.
- Added communication-number lifecycle transition support.
- Added communication-service lifecycle transitions across API and Filament adapters.

## 0.1.0

- Initial independently installable Communications domain module.
- Added service, number, usage-import, and provider lifecycle boundaries.
