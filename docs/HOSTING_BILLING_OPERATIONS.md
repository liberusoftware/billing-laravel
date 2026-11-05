# Hosting billing and customer purchasing

The home page lists active subscription plans offered by active order form templates. Configure `plan_ids` on each template; plans outside an active offer are not published. A plan link carries the template and plan selection into the authenticated client checkout. Checkout recalculates the quote and uses the existing order, invoice, payment, and hosting fulfillment flow. Hosting requires an existing domain; registration and transfer are separate.

## Subscription changes

Customers can request changes between products of the same type, team, and hosting server. Upgrades require an active, unexpired subscription and a higher product price. The invoice contains the prorated price difference for the remaining billing term. The provider change waits for payment of the invoice belonging to that subscription. Pending changes block competing requests.

`subscriptions:process-changes` runs hourly through the existing scheduler. It applies paid upgrades and due scheduled downgrades/cancellations. Successful changes update the recurring term price. Scheduled changes block renewal until processed, and successful renewal resets the start of the billing period. A provider rejection preserves the scheduled change and reports an error for investigation. A due cancellation terminates the remote hosting service, then marks the subscription cancelled and disables renewal.

Ensure Laravel's scheduler is running. These operations require working provider credentials and matching remote packages.

## Control panel integration

All six HTTP clients have explicit connection and overall timeouts and disable redirects. Mutating requests are not automatically retried: a timeout can occur after a provider has already performed the operation. Investigate remote state before retrying failed provisioning. Transport exception logs omit request URLs, which can contain credentials.

- cPanel uses WHM API 1 with `api.version=1`. Purchased packages define account limits instead of forced unlimited quotas.
- DirectAdmin uses its form API and login key, retaining the purchased package's resource limits.
- Plesk uses the XML API endpoint, nested operator/operation packets, escaped values, and nested result statuses. Provisioning creates a dedicated customer followed by its hosting subscription. Package and add-on changes resolve the remote plan GUID and modify the subscription rather than editing the shared service plan. If the second provisioning step fails, inspect the customer created by the first step before attempting recovery. The username identifies a dedicated Plesk customer; owner filters act on that customer's subscriptions.
- Virtualmin rejects malformed JSON responses as failed operations.
- Liberu encodes account and add-on path segments, honours explicit application errors, and accepts successful empty HTTP 204 responses.
- Softaculous retains its existing installer API contract and treats malformed JSON as failure.

Provider contract tests use mocked responses. They do not establish compatibility with every server version, credential permission, installed extension, or remote package. Validate lifecycle operations against staging servers before production use.

API references: [WHM API 1](https://api.docs.cpanel.net/whm/introduction), [Plesk XML API](https://docs.plesk.com/en-US/obsidian/api-rpc/about-xml-api.28709/), [Plesk plan switching](https://docs.plesk.com/en-US/obsidian/api-rpc/about-xml-api/reference/managing-subscriptions/switching-a-subscription-to-a-different-service-plan.66391/).

## Project time billing

Only stopped, billable, positive-duration, uninvoiced time belonging to the selected project is included. Rows are locked while the invoice is created and stamped. Task descriptions are eager loaded, and monetary line totals use the logged seconds before rounding to currency precision.
