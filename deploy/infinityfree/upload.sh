#!/usr/bin/env bash
#
# Optional FTP uploader for the InfinityFree package (uses lftp if present).
#
# Usage:
#   FTP_HOST=ftp.byet.io FTP_USER=<you> FTP_PASS=<pass> \
#     bash deploy/infinityfree/upload.sh
#
# If lftp is not installed this prints FileZilla instructions instead.
# Your credentials stay in env vars — they are never written to a file.
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ZIP="$SCRIPT_DIR/dist/tulona-infinityfree.zip"

: "${FTP_HOST:?Set FTP_HOST (e.g. ftp.byet.io)}"
: "${FTP_USER:?Set FTP_USER}"
: "${FTP_PASS:?Set FTP_PASS}"

[ -f "$ZIP" ] || { echo "Missing $ZIP — run build.sh first." >&2; exit 1; }

if command -v lftp >/dev/null; then
  TMP="$(mktemp -d)"
  unzip -q "$ZIP" -d "$TMP"
  echo "Uploading to $FTP_HOST:/htdocs via lftp …"
  lftp -e "set ftp:ssl-allow no; set net:timeout 30; mirror -R --no-perms --parallel=4 \"$TMP/htdocs\" /htdocs; quit" \
    -u "$FTP_USER","$FTP_PASS" "$FTP_HOST"
  rm -rf "$TMP"
  echo "✔ Uploaded. Allow a minute for InfinityFree to propagate."
else
  echo "lftp not installed. Do the upload with FileZilla instead:"
  echo
  echo "  1. Site Manager  →  Host: $FTP_HOST, User: $FTP_USER"
  echo "  2. Connect, open the remote 'htdocs' folder"
  echo "  3. Upload every file/folder from inside the zip (htdocs/) — one level up,"
  echo "     so htdocs contains app/, public/, .env, .htaccess, …"
  echo "  4. If the prompt asks about overwriting, choose 'Overwrite'."
fi