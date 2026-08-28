#!/usr/bin/env bash
#
# Tulona — Oracle Cloud Always Free VM bootstrap + deploy
#
# One-shot provisioning of a brand-new Ubuntu VM: installs PHP, Caddy, Composer,
# clones the app, migrates + seeds the SQLite DB, wires the Laravel scheduler
# (cron) and database queue worker (systemd).
#
# Idempotent: re-running after the app exists pulls the latest code,
# reinstalls deps and re-runs migrations.
#
# Usage (run on the VM, as the ubuntu/opc user):
#   export DOMAIN=yourdomain.com        # REQUIRED
#   export SEED=1                       # optional, default 1 (demo data)
#   bash <(curl -fsSL https://raw.githubusercontent.com/HanjalaShihab/Narrative-Lab/main/deploy/bootstrap.sh)
#
set -euo pipefail

# ---- Tunables ------------------------------------------------------------
REPO="${REPO:-https://github.com/HanjalaShihab/Narrative-Lab.git}"
BRANCH="${BRANCH:-main}"
APP_DIR="${APP_DIR:-/var/www/tulona}"
PHP_V="8.3"
SEED="${SEED:-1}"
DOMAIN="${DOMAIN:-}"

if [[ -z "$DOMAIN" ]]; then
  echo "ERROR: DOMAIN is required. Export it first, e.g.  export DOMAIN=tulona.example.com" >&2
  exit 1
fi

as_www() { sudo -u www-data HOME=/var/www bash -c "$*"; }

say()  { printf '\n=== %s ===\n' "$*"; }
die()  { echo "FATAL: $*" >&2; exit 1; }

# ---- 1. Base packages -----------------------------------------------------
say "Installing base packages"
sudo apt-get update -y
sudo DEBIAN_FRONTEND=noninteractive apt-get install -y \
  ca-certificates curl git unzip sqlite3 gnupg lsb-release software-properties-common

# ---- 2. PHP (Ondrej PPA) --------------------------------------------------
say "Installing PHP ${PHP_V}"
if ! command -v php >/dev/null; then
  sudo add-apt-repository -y ppa:ondrej/php
  sudo apt-get update -y
fi
sudo DEBIAN_FRONTEND=noninteractive apt-get install -y \
  php${PHP_V}-cli php${PHP_V}-fpm php${PHP_V}-sqlite3 \
  php${PHP_V}-mbstring php${PHP_V}-xml php${PHP_V}-curl \
  php${PHP_V}-zip php${PHP_V}-gd php${PHP_V}-intl php${PHP_V}-bcmath

command -v php${PHP_V} >/dev/null || die "PHP ${PHP_V} not found after install"

# ---- 3. Composer ----------------------------------------------------------
say "Installing Composer"
if ! command -v composer >/dev/null; then
  EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"
  php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
  php -r "if (hash_file('sha384', 'composer-setup.php') === '$EXPECTED_CHECKSUM') { echo 'Installer verified' . PHP_EOL; } else { unlink('composer-setup.php'); die('ERROR: Invalid composer installer' . PHP_EOL); }"
  sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
  rm -f composer-setup.php
fi

# ---- 4. Caddy (auto-HTTPS) ------------------------------------------------
say "Installing Caddy"
if ! command -v caddy >/dev/null; then
  sudo curl -fsSL https://dl.cloudsmith.io/public/caddy/stable/gpg.key |
    sudo gpg --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg
  echo "deb [signed-by=/usr/share/keyrings/caddy-stable-archive-keyring.gpg] https://dl.cloudsmith.io/public/caddy/stable/deb/debian any-version main" |
    sudo tee /etc/apt/sources.list.d/caddy-stable.list >/dev/null
  sudo apt-get update -y
  sudo DEBIAN_FRONTEND=noninteractive apt-get install -y caddy
fi

# ---- 5. Application -------------------------------------------------------
say "Deploying application to ${APP_DIR}"
if [[ ! -d "$APP_DIR/.git" ]]; then
  sudo git clone --depth 1 --branch "$BRANCH" "$REPO" "$APP_DIR"
else
  sudo -u www-data git -C "$APP_DIR" pull --ff-only
fi

# First-run .env
if [[ ! -f "$APP_DIR/.env" ]]; then
  say "Creating .env (from deploy/.env.production.example)"
  sudo cp "$APP_DIR/deploy/.env.production.example" "$APP_DIR/.env"
  sudo sed -i "s|^APP_URL=https://DOMAIN|APP_URL=https://$DOMAIN|" "$APP_DIR/.env"
  sudo -u www-data HOME=/var/www php "$APP_DIR/artisan" key:generate
fi

# SQLite database file
if [[ ! -f "$APP_DIR/database/database.sqlite" ]]; then
  sudo -u www-data bash -c "touch '$APP_DIR/database/database.sqlite'"
fi

# Ownership
sudo chown -R www-data:www-data "$APP_DIR"
sudo chmod -R u+rwX,g+rwX,o-w "$APP_DIR"

# Composer home for the www-data user
sudo mkdir -p /var/www/.composer
sudo chown -R www-data:www-data /var/www/.composer

# Dependencies
as_www "cd $APP_DIR && /usr/local/bin/composer install --no-dev --optimize-autoloader --no-interaction"

# Storage symlink for product images
if [[ ! -L "$APP_DIR/public/storage" ]]; then
  as_www "php '$APP_DIR/artisan' storage:link"
fi

# Migrations
as_www "php '$APP_DIR/artisan' migrate --force --no-interaction"

# Seed demo data only on a fresh database (flag-controlled)
if [[ "$SEED" != "0" ]]; then
  SEEDED_FLAG="$APP_DIR/storage/.seeded"
  if [[ ! -f "$SEEDED_FLAG" ]]; then
    say "Seeding catalog + demo content"
    as_www "php '$APP_DIR/artisan' db:seed --force --no-interaction"
    sudo -u www-data touch "$SEEDED_FLAG"
  fi
fi

# Cache
as_www "php '$APP_DIR/artisan' config:cache && php '$APP_DIR/artisan' view:cache"

# Writability
sudo chown -R www-data:www-data "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

# ---- 6. PHP-FPM pool ------------------------------------------------------
say "Configuring PHP-FPM"
sudo cp "$APP_DIR/deploy/php-fpm-pool.conf" "/etc/php/${PHP_V}/fpm/pool.d/tulona.conf"
sudo systemctl reload php${PHP_V}-fpm

# ---- 7. Caddy site --------------------------------------------------------
say "Configuring Caddy"
sudo cp "$APP_DIR/deploy/Caddyfile" /etc/caddy/Caddyfile
sudo sed -i "s/__DOMAIN__/${DOMAIN}/g" /etc/caddy/Caddyfile
sudo systemctl reload caddy

# ---- 8. Laravel scheduler (cron) + queue worker (systemd) ------------------
say "Installing Laravel scheduler cron"
( sudo crontab -u www-data -l 2>/dev/null
  echo "* * * * * cd ${APP_DIR} && /usr/bin/php${PHP_V} artisan schedule:run >> /dev/null 2>&1"
) | sort -u | sudo crontab -u www-data -

say "Installing queue worker service"
sudo cp "$APP_DIR/deploy/tulona-queue.service" /etc/systemd/system/tulona-queue.service
sudo sed -i "s|/usr/bin/php|/usr/bin/php${PHP_V}|" /etc/systemd/system/tulona-queue.service
sudo systemctl daemon-reload
sudo systemctl enable --now tulona-queue

# ---- 9. Firewall sanity ---------------------------------------------------
say "Checking firewall (if ufw is active it must allow 80/443 — cloud security list handles the rest)"

# ---- Done -----------------------------------------------------------------
say "DEPLOY COMPLETE"
echo
echo "  Site:        https://$DOMAIN"
echo "  App:         $APP_DIR"
echo "  DB:          $APP_DIR/database/database.sqlite"
echo
echo "  Demo logins (change these right away in the DB or via the UI):"
echo "    admin@tulona.test / password   (Super Admin)"
echo "    product@tulona.test / password (Product Manager)"
echo
echo "  HOTFIX (no full re-run needed):"
echo "    cd $APP_DIR && git pull && composer install --no-dev --optimize-autoloader"
echo "    sudo -u www-data php artisan migrate --force"
echo "    sudo systemctl reload php-fpm && sudo systemctl restart tulona-queue"
echo
echo "  The scheduler runs every minute (SyncMerchants every 6h)."
echo "  Alerts:  sudo journalctl -u tulona-queue -f"