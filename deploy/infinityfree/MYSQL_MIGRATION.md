# MySQL Migration — InfinityFree (sql101.infinityfree.com)

Your InfinityFree MySQL database was empty. This project was running on SQLite (`database/database.sqlite` with 50 products). The steps below preserve those 50 products and move them into MySQL so InfinityFree's phpMyAdmin hosts the live data.

## Credentials (from your request)

```
MySQL Host: sql101.infinityfree.com
DB Name:    if0_42760541_tulona
DB User:    if0_42760541
DB Pass:    Iamshihab1402  (FTP pass is same: Iamshihab1402, FTP user if0_42760541)
Site:       https://tulona.gt.tc
```

## What's already done locally

- SQLite backup: `database/database.sqlite` + timestamped `database/database.sqlite.bak.*` (50 products, 50 offers, 50 images).
- JSON exports: `database/products_export.json`, `categories_export.json`, etc.
- MySQL INSERT backup: `database/tulona_mysql_backup.sql` (INSERTs only — assumes tables already exist via migrations).
- Full MySQL import (schema + data): `deploy/infinityfree/tulona_mysql_full.sql` (DROP + CREATE + INSERT). Import this via phpMyAdmin.
- Production env: `deploy/infinityfree/.env.infinityfree.example` now uses `DB_CONNECTION=mysql` with the credentials above. `build.sh` will copy it to `htdocs/.env`.

## Option A — Full import via phpMyAdmin (recommended, no SSH needed)

1. **InfinityFree panel** → your site → **MySQL Databases** → confirm `if0_42760541_tulona` exists (create if not, with same name).
2. Open **phpMyAdmin** from the panel (or via `sql101.infinityfree.com`).
3. Select `if0_42760541_tulona` on the left.
4. **Import** → Choose file → select `deploy/infinityfree/tulona_mysql_full.sql` from this repo → **Go**.
   - If the import errors due to foreign keys, the file starts with `SET FOREIGN_KEY_CHECKS=0;` already. Just re-try.
   - You should see ~42 tables created and ~500 rows inserted. Verify: `products` should show 50 rows.
5. **Build & upload** the site:
   ```sh
   bash deploy/infinityfree/build.sh
   # then upload via FileZilla or:
   FTP_HOST=ftpupload.net FTP_USER=if0_42760541 FTP_PASS=Iamshihab1402 bash deploy/infinityfree/upload.sh
   ```
   The build's `.env` already points to MySQL — no manual edit needed after `build.sh` (it copies `.env.infinityfree.example`).

6. **Visit** `https://tulona.gt.tc` → home should list products, deals, etc. Test a product page: `https://tulona.gt.tc/product/<slug>` — Compare Stores should show the merchant(s) for that product.

7. **One-time**: verify API still works: `https://tulona.gt.tc/api/products` should return JSON.

## Option B — Migrate via webhook (if you prefer Laravel migrations to create the schema)

Use this if you want `artisan migrate` to run on the server instead of importing the full SQL.

1. Upload the site first (with MySQL `.env`):
   ```sh
   bash deploy/infinityfree/build.sh
   # upload htdocs/ (same as above)
   ```
2. In your browser, visit once:
   ```
   https://tulona.gt.tc/tulona/migrate/3a222f6571ebbe3f33147ea0bea71095444c0eed4eafdfb3a328fa258609
   ```
   (token = `SCHEDULER_TOKEN` from `.env`). This runs `php artisan migrate --force` on the server. Response should be `migrate done`.

3. Then import **data-only** backup via phpMyAdmin:
   - phpMyAdmin → `if0_42760541_tulona` → **Import** → `database/tulona_mysql_backup.sql` → Go.
   - This file only has INSERTs (assumes tables already exist from step 2).

4. Refresh the site.

## Verifying no data loss

```sql
SELECT COUNT(*) FROM products;   -- should be 50
SELECT COUNT(*) FROM offers;     -- 50
SELECT COUNT(*) FROM categories; -- 634+
SELECT COUNT(*) FROM merchants;  -- 6
```

Locally, the SQLite file still holds the same 50 products at `database/database.sqlite`. If anything goes wrong on InfinityFree, you can re-import or switch `.env` back to `DB_CONNECTION=sqlite` and re-upload the SQLite file.

## Local development still uses SQLite

`DB_CONNECTION=sqlite` remains in your local `.env`. Only the InfinityFree build uses MySQL. If you want to test MySQL locally, create a separate `.env` that points to `sql101.infinityfree.com` — but InfinityFree's MySQL is usually **not reachable from outside their network** (phpMyAdmin only). So keep local as SQLite and push uploads to InfinityFree.

## Troubleshooting

- **SQLSTATE[HY000] [2002] Connection refused** from the live site → MySQL host/user/pass wrong, or InfinityFree's MySQL server requires you to enable remote? Check panel → MySQL Databases → Host is `sql101.infinityfree.com` (not `localhost`).
- **Import foreign key errors** → the dump already disables `FOREIGN_KEY_CHECKS`. If phpMyAdmin still errors, import via the **SQL** tab manually pasting the file in chunks.
- **50 products disappear after import** → you imported into the wrong DB (check DB name). Re-select `if0_42760541_tulona` and re-import.
- **Site shows `View site` but product pages 404** → migrations didn't run. Use Option B step 2 or re-import the full SQL (`tulona_mysql_full.sql`).

## Files in this migration

| File | Purpose |
|---|---|
| `database/database.sqlite` | Live local SQLite (50 products preserved) |
| `database/database.sqlite.bak.*` | Timestamped backup |
| `database/tulona_mysql_backup.sql` | MySQL INSERTs only (requires tables via migrate) |
| `deploy/infinityfree/tulona_mysql_full.sql` | Full phpMyAdmin import (DROP+CREATE+INSERT) |
| `deploy/infinityfree/.env.infinityfree.example` | Production `.env` already pointed at MySQL |
| `routes/web.php` | Added `GET /tulona/migrate/{token}` helper for Option B |
