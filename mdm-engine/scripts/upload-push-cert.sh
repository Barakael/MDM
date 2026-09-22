#!/usr/bin/env bash
set -euo pipefail

LAB="${NANOMDM_LAB:-$HOME/nanomdm-lab}"
CERT_DIR="${LAB}/certs"
API_KEY="${NANOMDM_API_KEY:-nanomdm}"
URL="${NANO_MDM_BASE_URL:-http://127.0.0.1:9000}/v1/pushcert"

PUSH_PEM="${CERT_DIR}/push.pem"
PUSH_KEY="${CERT_DIR}/push.key"

if [[ ! -f "$PUSH_PEM" ]] || [[ ! -f "$PUSH_KEY" ]]; then
  echo "Missing certificate files."
  echo "  Expected: $PUSH_PEM"
  echo "  Expected: $PUSH_KEY"
  echo ""
  echo "See docs/apns-push-cert.md for how to obtain them."
  exit 1
fi

echo "Uploading push certificate to $URL"
response=$(cat "$PUSH_PEM" "$PUSH_KEY" | curl -sS -T - -u "nanomdm:${API_KEY}" "$URL")
echo "$response"

topic=$(echo "$response" | python3 -c "import json,sys; print(json.load(sys.stdin).get('topic',''))" 2>/dev/null || true)
if [[ -n "$topic" ]]; then
  echo ""
  echo "Topic: $topic"
  echo "Add this to ~/nanomdm-lab/profiles/enroll.mobileconfig under <key>Topic</key>"
fi
