# Tulona — tulona.gt.tc

Price-comparison & affiliate platform (Laravel 12). Catalog: products / brands / categories / merchants / offers, price history, deals & price drops, campaigns, landing pages, search, ` /go` affiliate redirects, analytics.

## Stack
PHP 8.3 · Laravel 12 · SQLite (dev) / MySQL (prod) · Vite + Tailwind 4 · Blade

## Requirements
PHP 8.3, Composer, Node 20+, SQLite

## Setup
```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm install
npm run build   # or npm run dev
php artisan serve
```

## Commands
```bash
composer dev      # serve + queue + vite (concurrently)
composer test     # php artisan test
php artisan migrate
npm run build
```

## Env
`APP_URL`, `DB_CONNECTION=sqlite` (local) or `mysql` (prod), `STARTECH_TRACKING_CODE`, `SCHEDULER_TOKEN` (for `/tulona/cron/{token}` and `/tulona/migrate/{token}` on shared hosting).

## Deploy (InfinityFree)
Project lives in `htdocs/`; root `.htaccess` rewrites to `public/`. See `deploy/infinityfree/` for `.htaccess`, `build.sh`, `upload.sh` and MySQL notes. Use `public/import.sql` + `public/runimport.php` only for one-time MySQL bootstrap, then remove.

## Structure
`routes/web.php` · `routes/admin.php` · `app/Http/Controllers/Admin` · `resources/views` · `database/migrations`

## License
MIT
