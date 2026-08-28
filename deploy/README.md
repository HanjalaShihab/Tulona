# Tulona on Oracle Cloud (Always Free) — Deployment Guide

Runs **Tulona** (Laravel 13 + SQLite) on a free Oracle Cloud VM with Caddy
(auto-HTTPS) + PHP-FPM. Free forever, full disk for the SQLite DB and product
images, cron + queue worker for the price sync and imports.

> Requires: a **domain name you control** (Caddy needs one for free TLS) and a
> **credit card for Oracle's signup** (used for identity only on the free tier).

---

## 1. Create the VM

1. Sign up at https://signup.cloud.oracle.com (free tier, no charge).
2. Console → **Compute → Instances → Create instance**.
3. Name: `tulona`. Pick:
   - **Image**: `Canonical Ubuntu 24.04` (Oracle Linux works too, but Ubuntu below matches the script).
   - **Shape**: use **"Always Free eligible"** — `VM.Standard.A1.Flex` (Arm) with
     **4 OCPU / 24 GB RAM**, or `VM.Standard.E2.1.Micro` (AMD, 1 OCPU / 1 GB).
     Arm is much better — select it.
4. **SSH**: paste your public key (Getting Started). You'll need it to log in.
5. **Create** → wait ~2 minutes.

### 2. Enable the firewall (easy to miss!)

Oracle blocks outbound web traffic on its free instances by default until you
open the ports. On the instance page:

1. Instance page → **Attached VNICs** → click the VNIC → **Security Lists** → default.
2. **Add Ingress Rules** for BOTH:
   | Source CIDR | IP Protocol | Destination Port |
   |---|---|---|
   | `0.0.0.0/0` | TCP | 80 (HTTP) |
   | `0.0.0.0/0` | TCP | 443 (HTTPS) |

(SSH/22 is usually pre-opened. If not, add it too — on the Arm shape it lives in
the NSG.)

### 3. Reserve a public IP + point DNS

The default public IP changes on every stop/start. Make it permanent:

1. Instance → **Attached VNICs** → **IPv4 addresses → Actions → Promote to reserved**.
2. At your domain registrar, add two **A records**:
   - `tulona.example.com  → <reserved public IP>`
   - `www.tulona.example.com → <reserved public IP>`

---

## 4. Run the deployment

```sh
ssh -i ~/.ssh/your_key ubuntu@<public-ip>
```

```sh
export DOMAIN=tulona.example.com
bash <(curl -fsSL https://raw.githubusercontent.com/HanjalaShihab/Narrative-Lab/main/deploy/bootstrap.sh)
```

The script is idempotent and does everything: PHP 8.3 + extensions, Composer,
Caddy, app clone, `.env`, `APP_KEY`, SQLite `migrate` + seed, storage symlink,
PHP-FPM pool, cron (`schedule:run` → SyncMerchants every 6 h), and a systemd
queue worker.

Sit back — Caddy fetches a Let's Encrypt certificate automatically on first
hit. Then browse to `https://tulona.example.com`.

---

## 5. First steps after launch

- **Log in** (`/login`) with the demo admin:
  `admin@tulona.test` / `password` (also `product@tulona.test` / `password`).
- **Change those passwords immediately** or delete the demo users.
- Under **Stores (Merchants)**, review connectors URLs — demo feeds point at
  placeholder data.
- If you want an empty launch site (no demo products), wipe with
  `php artisan migrate:fresh --force` and seed only the catalog:
  `php artisan db:seed --force` but edit `DatabaseSeeder` to your liking.

## 6. Updating the site

`git pull` on the VM, then:

```sh
cd /var/www/tulona
sudo -u www-data git pull
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan config:cache
sudo systemctl reload php8.3-fpm
sudo systemctl restart tulona-queue
```

Simplest alternative: rebuild everything in one shot — re-run `bootstrap.sh`
(step 4). It pulls, migrates, re-caches, done.

## 7. Day-to-day ops

| Task | Command |
|---|---|
| Queue logs | `sudo journalctl -u tulona-queue -f` |
| App logs | `tail -f /var/www/tulona/storage/logs/laravel.log` |
| HTTP logs | `sudo journalctl -u caddy -f` |
| Restart worker | `sudo systemctl restart tulona-queue` |
| See scheduled tasks | `sudo -u www-data php /var/www/tulona/artisan schedule:list` |
| Run price sync now | `sudo -u www-data php /var/www/tulona/artisan tulona:sync` |

## 8. Security notes

- Ports: only 22 / 80 / 443 open in the Oracle security list.
- `.env` is never web-accessible (Caddy's root is `public/` and the Caddyfile
  blocks hidden files).
- Free-tier VMs are reclaimed if *idle*, not if lightly used — keep the
  scheduler on (it pings daily anyway).
- Back up the SQLite file + `storage/app/public/products` to `tahoe-lafs`, a
  second object, or `scp` periodically: everything lives in `/var/www/tulona`.

## 9. Costs

**$0.** Compute (Ampere A1 4 OCPU/24 GB or E2.1 Micro), IPv4, TLS certs, PHP,
Caddy — all free under the Always Free tier. Your only non-zero bills would be
a domain name and (optionally) MySQL/S3 if you migrate off SQLite later.

---

## Files in `deploy/`

| File | Purpose |
|---|---|
| `bootstrap.sh` | Idempotent one-shot provisioning + deploy |
| `Caddyfile` | Caddy config (auto-HTTPS, PHP-FPM, long-cache assets) |
| `php-fpm-pool.conf` | PHP-FPM pool tuned for 1–8 workers |
| `tulona-queue.service` | systemd unit for `artisan queue:work` |
| `.env.production.example` | Live `.env` template |