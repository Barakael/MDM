#!/usr/bin/env bash
set -euo pipefail

LAB="${NANOMDM_LAB:-$HOME/nanomdm-lab}"
PORT="${SCEP_PORT:-8080}"
CHALLENGE="${SCEP_CHALLENGE:-nanomdm}"

if [[ ! -f "$LAB/ca.pem" ]]; then
  echo "Run init-ca first: mdm-engine/scripts/init-ca.sh"
  exit 1
fi

echo "Starting SCEP on :$PORT (challenge: $CHALLENGE)"
exec "$LAB/bin/scep" -depot "$LAB/depot" -port "$PORT" -challenge "$CHALLENGE" -debug
