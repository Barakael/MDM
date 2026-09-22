#!/usr/bin/env bash
set -euo pipefail

LAB="${NANOMDM_LAB:-$HOME/nanomdm-lab}"
CHALLENGE="${SCEP_CHALLENGE:-nanomdm}"

mkdir -p "$LAB/depot"

if [[ ! -x "$LAB/bin/scep" ]]; then
  echo "SCEP binary missing. Run: mdm-engine/scripts/install-scep.sh"
  exit 1
fi

if [[ ! -f "$LAB/depot/ca.pem" ]]; then
  echo "Initializing SCEP CA in $LAB/depot"
  (cd "$LAB" && ./bin/scep ca -init -depot depot)
fi

cp -f "$LAB/depot/ca.pem" "$LAB/ca.pem"
echo "CA ready: $LAB/ca.pem"
echo "Start SCEP: mdm-engine/scripts/start-scep.sh"
echo "Challenge password: $CHALLENGE"
