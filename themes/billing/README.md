# Billing Workspace Theme

`theme-billing` is the responsive portal presentation for the billing application. It provides a focused workspace shell, compact navigation, responsive summary cards, and a dashboard view that consumes the existing Livewire metrics without moving billing logic into the theme.

## Installation

The host project includes this theme as a path Composer package:

```bash
composer install
npm install
npm run build
```

Select it from the existing theme switcher or set `THEME_DEFAULT=billing` for a local default. The theme inherits the accessible `base` theme and safely falls back to the configured default when unavailable.

## Included surfaces

- `resources/views/layouts/app.blade.php` — responsive sidebar and topbar application shell.
- `resources/views/livewire/dashboard.blade.php` — billing dashboard presentation for the existing `App\\Livewire\\Dashboard` component.
- `resources/css/app.css` — indigo/slate billing visual system with mobile navigation, reduced-motion support, and keyboard-visible focus.
- `resources/js/app.js` — small progressive enhancement for the mobile sidebar.

The theme contains presentation only. Authorization, queries, mutations, and billing calculations remain owned by the application and billing modules.
