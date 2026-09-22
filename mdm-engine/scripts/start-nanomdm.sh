#!/usr/bin/env bash
set -euo pipefail

# Prefer Laravel-aligned env names; keep NANOMDM_* aliases for lab convenience.
LAB="${NANOMDM_LAB:-$HOME/nanomdm-lab}"
PORT="${NANOMDM_PORT:-9000}"
API_KEY="${NANO_MDM_API_KEY:-${NANOMDM_API_KEY:-nanomdm}}"
WEBHOOK_URL="${NANO_MDM_WEBHOOK_URL:-${NANOMDM_WEBHOOK_URL:-}}"
WEBHOOK_HMAC="${NANO_MDM_WEBHOOK_SECRET:-${NANOMDM_WEBHOOK_HMAC_KEY:-}}"

if [[ ! -x "$LAB/bin/nanomdm" ]]; then
  echo "Copy nanomdm binary to $LAB/bin/nanomdm first."
  echo "Example: cp ~/Downloads/nanomdm-darwin-amd64-v0.9.0/nanomdm-darwin-amd64 $LAB/bin/nanomdm"
  exit 1
fi

if [[ ! -f "$LAB/ca.pem" ]]; then
  echo "Run init-ca first: mdm-engine/scripts/init-ca.sh"
  exit 1
fi

mkdir -p "$LAB/nanomdm-db"

ARGS=(
  -ca "$LAB/ca.pem"
  -api "$API_KEY"
  -storage filekv
  -storage-dsn "$LAB/nanomdm-db"
  -listen ":$PORT"
  -debug
)

if [[ -n "$WEBHOOK_URL" ]]; then
  ARGS+=(-webhook-url "$WEBHOOK_URL")
  echo "Webhook: $WEBHOOK_URL"
fi

if [[ -n "$WEBHOOK_HMAC" ]]; then
  ARGS+=(-webhook-hmac-key "$WEBHOOK_HMAC")
fi

echo "Starting NanoMDM on :$PORT (API user: nanomdm, key: $API_KEY)"
exec "$LAB/bin/nanomdm" "${ARGS[@]}"
