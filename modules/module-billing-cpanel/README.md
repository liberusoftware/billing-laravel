# Billing cPanel

Optional cPanel/WHM adapter for `module-billing-hosting`. It registers the
`cpanel` hosting driver and supports account provisioning, suspension,
unsuspension, package changes, addons, and termination through WHM's JSON API.

Pass server credentials under `server` (`api_url`, `username`, `api_token`) and
account details under `account` (`username`, `domain`, and `package`).
