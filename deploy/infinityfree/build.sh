#!/usr/bin/env bash
#
# Build the ready-to-upload InfinityFree package for Tulona.
#
# Produces:  deploy/infinityfree/dist/tulona-infinityfree.zip
# Layout:    the whole Laravel app in a top-level `htdocs/` folder, exactly as
#            InfinityFree documents for Laravel. Includes the live SQLite DB
#            (with your data) and uploaded product images, production .env and
#            the root .htaccess that routes /storage/* and public/*.
#
# Run from anywhere:  bash deploy/infinityfree/build.sh
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"

OUT_DIR="$SCRIPT_DIR/dist"
OUT_ZIP="$OUT_DIR/tulona-infinityfree.zip"
STAGE="$(mktemp -d)"
HTDOCS="$STAGE/htdocs"

command -v composer >/dev/null || { echo "ERROR: composer not found" >&2; exit 1; }
command -v zip     >/dev/null || { echo "ERROR: zip not found" >&2; exit 1; }

echo "Staging app into $(basename "$STAGE") …"

mkdir -p "$HTDOCS"

rsync -a \
  --exclude '.git' \
  --exclude '.claude' \
  --exclude '.vscode' \
  --exclude '.env' \
  --exclude '.env.production' \
  --exclude '.phpunit.result.cache' \
  --exclude '.mcp.json' \
  --exclude 'node_modules' \
  --exclude 'vendor' \
  --exclude 'tests' \
  --exclude 'deploy' \
  --exclude '*.md' \
  --exclude 'package.json' \
  --exclude 'package-lock.json' \
  --exclude '.npmrc' \
  --exclude 'phpunit.xml' \
  --exclude 'boost.json' \
  --exclude 'inspect_cache.php' \
  --exclude 'public/build' \
  --exclude 'public/hot' \
  "$ROOT/" "$HTDOCS/"

echo "Installing production dependencies (composer --no-dev) …"
(cd "$HTDOCS" && composer install --no-dev --optimize-autoloader --no-interaction --quiet)

echo "Wiring production .env, .htaccess, database …"
cp "$SCRIPT_DIR/.env.infinityfree.example" "$HTDOCS/.env"
cp "$SCRIPT_DIR/.htaccess"                 "$HTDOCS/.htaccess"
[ -f "$ROOT/database/database.sqlite" ] && cp "$ROOT/database/database.sqlite" "$HTDOCS/database/database.sqlite"

echo "Cleaning runtime junk (compiled views, caches, logs) …"
find "$HTDOCS/storage/framework/views" -type f ! -name '.gitignore' -delete 2>/dev/null || true
find "$HTDOCS/storage/framework/cache/data" -type f -delete 2>/dev/null || true
find "$HTDOCS/storage/framework/sessions" -type f ! -name '.gitignore' -delete 2>/dev/null || true
find "$HTDOCS/bootstrap/cache" -type f ! -name '.gitignore' -delete 2>/dev/null || true
rm -f "$HTDOCS"/storage/logs/*.log* 2>/dev/null || true

# Ensure directories Laravel/queue needs exist and are writable (owner = you on shared hosting)
mkdir -p "$HTDOCS/storage/framework/views" \
         "$HTDOCS/storage/framework/sessions" \
         "$HTDOCS/storage/framework/cache/data" \
         "$HTDOCS/storage/framework/testing" \
         "$HTDOCS/storage/logs" \
         "$HTDOCS/bootstrap/cache"

mkdir -p "$OUT_DIR"
echo '**' > "$OUT_DIR/.gitignore"

echo "Zipping …"
(cd "$STAGE" && zip -rq "$OUT_ZIP" htdocs)

echo
echo "✔ Built: $OUT_ZIP  ($(du -h "$OUT_ZIP" | cut -f1))"
echo
echo "Next:"
echo "  1. Edit $SCRIPT_DIR/.env.infinityfree.example → set APP_URL and SCHEDULER_TOKEN"
echo "  2. Re-run this script so the .env lands inside the package"
echo "  3. Upload htdocs/ from the zip into your InfinityFree htdocs/ folder"
echo "     (FileZilla, or:  FTP_HOST= ftp.byet.io FTP_USER=<user> FTP_PASS=<pass> bash $SCRIPT_DIR/upload.sh)"
echo "  4. In the InfinityFree panel: set up free SSL; keep PHP 8.3"
echo "  5. Configure cron-job.org to hit  https://<site>/tulona/cron/<token> every 10 minutes"