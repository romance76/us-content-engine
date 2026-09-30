# us-content-engine

A self-managed (no WordPress) Laravel app for running a niche, AI-assisted US content site: an external research/writing pipeline pushes drafts in through an API, a human reviews and publishes them from `/admin`, and the public site serves them with basic SEO (meta tags, sitemap, JSON-LD).

Built deliberately **portable**: no part of the app is tied to a specific domain or server. Moving the whole project to a brand-new `.com` later is: point DNS, copy `.env`, restore the DB dump. See [Portability](#portability-moving-to-a-new-domain-later).

## Why this exists (context)

This is step one of testing whether a narrow, US-focused niche (home maintenance, parenting, small-business ops, etc.) can sustain an AI-assisted content operation without tripping Google's [scaled content abuse](https://developers.google.com/search/blog/2024/03/core-update-spam-policies) policy. The design choice that matters most: **every AI-drafted article lands as `draft`, never `published`, until a human reviews it in `/admin`.** Nothing here auto-publishes.

## Architecture

- **Laravel 13**, Blade + Tailwind (Breeze for auth) — no WordPress, no page builder, just plain code you can read.
- **Single admin user** — public self-registration is disabled. The admin account is created/updated idempotently via `php artisan db:seed` (reads `ADMIN_EMAIL` / `ADMIN_PASSWORD` from `.env`), safe to re-run on every deploy.
- **`articles` table**: `status` is `draft` → `in_review` → `published`. Only `published` (and `published_at <= now()`) rows are ever shown to anonymous visitors.
- **`keywords` table**: a queue of search terms/questions the content pipeline is working through, with `source`, `search_volume`, and a `status` (`new` / `queued` / `used` / `rejected`).
- **Ingest API** (`routes/api.php`) — how an external automation (a script, a scheduled job, a separate AI agent) feeds this site:
  - `POST /api/ingest/articles` — creates a `draft` article. Body: `title`, `body` (HTML), optional `excerpt`, `keyword_term` (auto-creates the keyword if new), `meta_title`, `meta_description`, `cover_image_url`.
  - `POST /api/ingest/keywords` — bulk-upserts keyword candidates (deduped on `term`). Body: `{"keywords":[{"term":"...", "source":"...", "search_volume":123}]}`.
  - `GET /api/ingest/keywords` — returns the current `new` keyword queue, for the pipeline to decide what to write next.
  - All three require `Authorization: Bearer <INGEST_API_TOKEN>` (shared secret, set in `.env`).
- **Admin** (`/admin`, requires login): article list with status filters, create/edit form, one-click publish/unpublish, keyword queue management.
- **Public site**: `/` (paginated published articles), `/articles/{slug}`, `/sitemap.xml`. Each article renders `<meta name="description">`, Open Graph tags, and an `Article` JSON-LD block.

## Local setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
# set ADMIN_EMAIL / ADMIN_PASSWORD / INGEST_API_TOKEN in .env
php artisan migrate
php artisan db:seed          # creates the admin user
npm run build                 # or `npm run dev` while working on views
php artisan serve
```

Log in at `/login`, land in `/admin/articles`.

## Feeding it content (the AI pipeline side)

This repo intentionally does **not** include the research/writing pipeline itself — that's a separate concern (whatever script, cron job, or agent does keyword research + drafting) and can be built/iterated on independently without touching this app. It only needs to speak HTTP:

```bash
# discover keywords
curl -X POST https://your-domain.com/api/ingest/keywords \
  -H "Authorization: Bearer $INGEST_API_TOKEN" -H "Content-Type: application/json" \
  -d '{"keywords":[{"term":"heat pump tax credit 2026","source":"search_console","search_volume":900}]}'

# push a drafted article
curl -X POST https://your-domain.com/api/ingest/articles \
  -H "Authorization: Bearer $INGEST_API_TOKEN" -H "Content-Type: application/json" \
  -d '{"title":"...", "body":"<p>...</p>", "keyword_term":"heat pump tax credit 2026"}'
```

The draft then shows up in `/admin/articles?status=draft` for review.

## Deploying to a DigitalOcean droplet

1. Provision a small droplet (Ubuntu, 1GB is enough to start), install PHP 8.3+, `php8.3-fpm`, nginx, composer, node, certbot, MySQL/SQLite.
2. `git clone` this repo to `/var/www/us-content-engine`, copy `.env.example` → `.env`, fill in values, `php artisan key:generate`, `php artisan migrate`, `php artisan db:seed`.
3. Point an nginx server block at `public/`, same shape as a standard Laravel vhost (see `awesomekorean`'s droplet for a reference config).
4. `certbot --nginx -d your-domain.com`.
5. Re-deploys after that: `APP_DIR=/var/www/us-content-engine PHP_BIN=php8.3 ./deploy.sh` (or wire it into a systemd/cron/CI hook — same shape as `awesomekorean/deploy.sh`).

## Portability (moving to a new domain later)

Nothing here hardcodes a domain or server path:

- **URLs** are all generated from `APP_URL` / the current request — nothing is hardcoded in views or the DB.
- **Uploaded images** should go through the `s3` filesystem disk (DigitalOcean Spaces is S3-compatible — see the commented `AWS_*` block in `.env.example`), not local disk, so they survive a server move untouched.
- **The database** is the only real state. Moving = `mysqldump`/`sqlite3 .dump` on the old server, restore on the new one (or new managed DB), point the new `.env` at it.

To split this off onto its own domain once it's validated:

1. Buy the new domain, point DNS at the (new or same) droplet.
2. Update `APP_URL` (and `AWS_URL` if using Spaces) in `.env`.
3. Re-run `certbot` for the new domain.
4. `php artisan optimize:clear && php artisan optimize`.

No code changes required.
