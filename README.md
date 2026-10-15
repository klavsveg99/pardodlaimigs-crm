# Pārdod Laimīgs CRM

Laravel + Filament CRM for pardodlaimigs.lv, plus a Laravel-rendered version of
the public website.

## The public site

The public site is **built but not live**: pardodlaimigs.lv is still served by
WordPress, and the new design is published as a static preview on
`https://pardodlaimigs.lv/new/` (see the separate `pardodlaimigs-website`
project for that build).

Everything for the switch is already in this repository but stays dormant until
it is switched on:

- `config/site.php` -> `enabled` (`PUBLIC_SITE_ENABLED`). While this is false the
  public routes are not registered at all and the CRM behaves exactly as before.
- `app/Http/Controllers/PublicSiteController.php` renders the pages (home,
  listings, property, blog, posts, content pages, agent pages, sitemap) and
  `PublicSiteLeadController.php` takes the valuation form into the same
  `wpform_entries` inbox the CRM already reads. The form checks the email shape,
  the phone number against the chosen country's dial code
  (`config/site.php` -> `phone_prefixes`) and a Google reCAPTCHA v3 token: the
  keys live in `RECAPTCHA_SITE_KEY` / `RECAPTCHA_SECRET` (the same pair the
  WordPress site uses) and without a secret the check is skipped rather than
  failed.
- `resources/views/site/*` are the views, `public/site-assets/*` the CSS, JS,
  fonts and images they use.
- Migrated WordPress content lives in `resources/site/` (articles and pages in
  all four languages, plus a snapshot of the current listings for
  `PUBLIC_SITE_PREVIEW_DATA=true`, which is the default outside production).

Switching it on means setting `PUBLIC_SITE_ENABLED=true`, pointing
`PUBLIC_SITE_HOST`/`PUBLIC_SITE_URL` at the live domain, and serving
`pardodlaimigs.lv` from this app's `public/` directory. From that moment the CRM
panel is pinned to `APP_HOST` and the public host is treated as the site.

### Trying it locally

```sh
# PUBLIC_SITE_ENABLED=true in .env, then
php artisan serve --host=127.0.0.1 --port=8000
```

With the flag on, the public site answers on `PUBLIC_SITE_HOST` (so
`http://127.0.0.1:8000`) while the CRM panel stays on `http://localhost:8000`.
Set the flag back to false (or use `localhost`) if you want the panel on both.
