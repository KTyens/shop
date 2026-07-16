#!/usr/bin/env bash
# Start local product admin with raised PHP upload limits (default PHP is often 2M).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
HOST="${CRTLU_ADMIN_HOST:-127.0.0.1}"
PORT="${CRTLU_ADMIN_PORT:-8088}"

echo "CRTLU admin → http://${HOST}:${PORT}/admin/products.php"
echo "  upload_max_filesize=64M  post_max_size=80M  memory_limit=512M"
echo "  Front preview expects Astro at CRTLU_BASE_URL (default http://127.0.0.1:4322)"
echo "  Stop with Ctrl+C"
echo

cd "$ROOT"
exec php \
  -d upload_max_filesize=64M \
  -d post_max_size=80M \
  -d memory_limit=512M \
  -d max_file_uploads=50 \
  -S "${HOST}:${PORT}" \
  -t "$ROOT"
