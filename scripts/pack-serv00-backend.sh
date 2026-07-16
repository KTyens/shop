#!/usr/bin/env bash
# Package PHP backend for Serv00 (api.crtlu.me) — excludes secrets & node_modules.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT="${1:-$ROOT/../crtlu-serv00-backend-$(date +%Y%m%d).zip}"
cd "$ROOT"

rm -f "$OUT"
zip -r "$OUT" \
  api \
  admin \
  data/catalog.json \
  data/coupons.json \
  database \
  .htaccess \
  docs/deployment-guide-zh.md \
  docs/DEPLOYMENT.md \
  -x '*.DS_Store' \
  -x 'api/config.local.php' \
  -x 'api/config.local.example.php.bak' \
  -x 'admin/serve.sh'

echo "Created: $OUT"
echo "Verify secrets excluded:"
unzip -l "$OUT" | grep -E 'config\.local\.php' && exit 1 || echo "OK — no config.local.php"
echo
echo "Upload to Serv00 website root for api.crtlu.me:"
echo "  admin/  api/  data/  database/  .htaccess"
echo "Keep existing api/config.local.php on the server (do not overwrite with empty)."
echo "Then open: https://api.crtlu.me/admin/"
