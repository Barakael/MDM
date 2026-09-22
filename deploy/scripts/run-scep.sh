#!/usr/bin/env bash
set -euo pipefail
ROOT=/var/www/mdm.wayda.co.tz
LAB="$ROOT/mdm-lab"
# shellcheck disable=SC1091
set -a
source "$ROOT/backend/.env"
set +a

CHALLENGE="${NANO_SCEP_CHALLENGE:-nanomdm}"
exec "$LAB/bin/scep" -depot "$LAB/depot" -port 8080 -challenge "$CHALLENGE" -debug
