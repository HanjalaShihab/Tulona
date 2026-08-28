# Deploying Tulona on InfinityFree

Free shared hosting (PHP **8.3**, free SSL, custom domains). No SSH, no Composer,
no cron — so everything is **built locally and uploaded**, the SQLite DB ships
inside the package, the scheduler is driven by a webhook, and jobs run inline.

Run cost: **$0.** Fair-use cap ≈ 50k hits/day.

---

## What's in here

| File | Purpose |
|---|---|
| `build.sh` | Builds `dist/tulona-infinityfree.zip` (whole app in an `htdocs/` folder) |
| `.env.infinityfree.example` | Production `.env` — set `APP_URL` + `SCHEDULER_TOKEN` |
| `.htaccess` | Root rules: `/storage/* → storage/app/public/*`, everything else → `public/` |
| `upload.sh` | Optional lftp uploader (FileZilla fallback instructions) |

## Code added for shared hosting

One route in `routes/web.php`:

    GET /tulona/cron/<token>

Runs `php artisan schedule:run` (so `tulona:sync` — price sync every 6h — keeps
running). Disabled unless `SCHEDULER_TOKEN` is set; returns 403 on wrong token.

---

## 1. Prepare (once)

```sh
# 1. Edit the prod env — two values matter:
#      APP_URL=https://<your-subdomain>.infinityfreeapp.com
#      SCHEDULER_TOKEN=  <- php -r 'echo bin2hex(random_bytes(30));'
nano deploy/infinityfree/.env.infinityfree.example

# 2. Build the package
bash deploy/infinityfree/build.sh

# artifact: deploy/infinityfree/dist/tulona-infinityfree.zip
```

## 2. Get your FTP + domain from InfinityFree

In the [control panel](https://dash.infinityfree.com): **Accounts → your site** →
FTP details are under **FTP Details** (host like `ftp.byet.io`, user like
`epiz_12345678`), and your free subdomain shows next to the site, e.g.
`tulona.infinityfreeapp.com`.

## 3. Upload

**Option A — FileZilla (recommended):**
1. Open FileZilla → Site Manager → new site: Host `ftp.byet.io`, User/Pass yours, port 21.
2. Connect, open remote `htdocs`.
3. Extract the zip locally; upload the **contents** of its `htdocs/` folder so the
   remote `htdocs/` gets `app/`, `public/`, `.env`, `.htaccess`, `artisan`, …
   (Allow overwrite for anything already there.)

**Option B — script:**
```sh
FTP_HOST=ftp.byet.io FTP_USER=epiz_XXXX FTP_PASS=yourpass \
  bash deploy/infinityfree/upload.sh
```
(uses `lftp`; prints FileZilla steps if it's not installed)

## 4. Panel settings

- **PHP version**: ensure **8.3** is selected for the site (default since 2025).
- **Free SSL**: Control Panel → **SSL/TLS** → enable the free certificate for
  your subdomain. Takes up to a few hours to issue.
- If a **500 error** appears: the most common cause on shared hosts is unwritable
  dirs — re-upload is enough (they keep your ownership), or ask in the panel that
  `storage/` and `bootstrap/cache/` be writable.

## 5. Kick the tires

- Visit `https://<site>/` — home page renders.
- Log in at `/admin/login` with demo admin `admin@tulona.test` / `password` (change it!).
- Admin → Products → open a product: its images come from their hosted URLs, so a
  working set means the site is healthy.

## 6. Scheduled tasks (no cron on InfinityFree)

1. Create a free account at [cron-job.org](https://cron-job.org).
2. New job:
   - URL: `https://<site>/tulona/cron/<your SCHEDULER_TOKEN>`
   - Schedule: every **10 minutes**.
3. It triggers the Laravel scheduler; `tulona:sync` itself runs **every 6 hours**.

> Imports/scrapes run inside the web request (`QUEUE_CONNECTION=sync`). Large
> batches may be slow or hit the 60s request cap — keep batches small.

---

## Updates / hotfixes

Same flow as the first deploy: rebuild locally, re-upload changed files,
**never** overwrite `htdocs/.env` (or back it up first) and skip re-uploading
`database/database.sqlite` unless you intend to reset data.

## Backup

Everything that matters is 2 files:
- `htdocs/database/database.sqlite` — the whole site DB
- `htdocs/storage/app/public/products/` — uploaded product images

Download them occasionally from FileZilla.

## Honest limits

- ~50k hits/day fair use, 60s max request time, shared-box performance.
- Great for a personal/demo site. For real traffic, graduate to a cheap VPS or
  Koyeb/Render later (SQLite and Postgres paths are both documented there).