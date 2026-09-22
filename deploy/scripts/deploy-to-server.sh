#!/usr/bin/env bash
# Sync local MDM tree to 161.97.182.204 and run bootstrap.
set -euo pipefail

HOST="${MDM_DEPLOY_HOST:-root@161.97.182.204}"
REMOTE=/var/www/mdm.wayda.co.tz
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"

echo "==> Rsync project to ${HOST}:${REMOTE}"
ssh "$HOST" "mkdir -p ${REMOTE}"
rsync -az --delete \
  --exclude '.git' \
  --exclude 'backend/vendor' \
  --exclude 'backend/node_modules' \
  --exclude 'frontend/node_modules' \
  --exclude 'frontend/dist' \
  --exclude 'backend/storage/logs/*' \
  --exclude 'backend/.env' \
  --exclude '.env' \
  "$ROOT/" "${HOST}:${REMOTE}/"

echo "==> Copy push cert from lab (if present)"
if [[ -f "$HOME/nanomdm-lab/certs/push.pem" && -f "$HOME/nanomdm-lab/certs/push.key" ]]; then
  ssh "$HOST" "mkdir -p ${REMOTE}/mdm-lab/certs"
  scp "$HOME/nanomdm-lab/certs/push.pem" "$HOME/nanomdm-lab/certs/push.key" \
    "${HOST}:${REMOTE}/mdm-lab/certs/"
fi

echo "==> Remote bootstrap"
ssh "$HOST" "chmod +x ${REMOTE}/deploy/scripts/*.sh && bash ${REMOTE}/deploy/scripts/bootstrap-server.sh"

echo "Done. Open https://mdm.wayda.co.tz after DNS propagates."
