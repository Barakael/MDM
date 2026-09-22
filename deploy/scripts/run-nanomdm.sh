#!/usr/bin/env bash
set -euo pipefail
ROOT=/var/www/mdm.wayda.co.tz
LAB="$ROOT/mdm-lab"
# shellcheck disable=SC1091
set -a
source "$ROOT/backend/.env"
set +a

API_KEY="${NANO_MDM_API_KEY:-nanomdm}"
WEBHOOK_URL="${NANO_MDM_WEBHOOK_URL:-}"
WEBHOOK_HMAC="${NANO_MDM_WEBHOOK_SECRET:-}"

ARGS=(
  -ca "$LAB/ca.pem"
  -api "$API_KEY"
  -storage filekv
  -storage-dsn "$LAB/nanomdm-db"
  -listen 127.0.0.1:9000
  -debug
)

if [[ -n "$WEBHOOK_URL" ]]; then
  ARGS+=(-webhook-url "$WEBHOOK_URL")
fi
if [[ -n "$WEBHOOK_HMAC" ]]; then
  ARGS+=(-webhook-hmac-key "$WEBHOOK_HMAC")
fi

exec "$LAB/bin/nanomdm" "${ARGS[@]}"
