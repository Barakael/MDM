#!/usr/bin/env bash
# Bootstrap MDM Platform on 161.97.182.204 for https://mdm.wayda.co.tz
# Run as root on the server after code is synced to /var/www/mdm.wayda.co.tz
set -euo pipefail

ROOT=/var/www/mdm.wayda.co.tz
LAB="$ROOT/mdm-lab"
DOMAIN=mdm.wayda.co.tz
MAILCOW=/opt/mailcow-dockerized

echo "==> Installing packages"
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y -qq redis-server php8.4-mysql php8.4-redis php8.4-cli php8.4-xml php8.4-mbstring php8.4-curl php8.4-zip unzip curl openssl rsync

systemctl enable --now redis-server

echo "==> Creating MySQL database (mailcow MariaDB on 13306)"
DB_PASS="${MDM_DB_PASSWORD:-$(openssl rand -base64 24 | tr -d '/+=' | head -c 24)}"
API_KEY="${NANO_MDM_API_KEY:-$(openssl rand -hex 16)}"
SCEP_CHALLENGE="${NANO_SCEP_CHALLENGE:-$(openssl rand -hex 8)}"
WEBHOOK_SECRET="${NANO_MDM_WEBHOOK_SECRET:-$(openssl rand -hex 16)}"

# Mailcow MySQL root password from mailcow.conf
DBROOT_PASS=""
if [[ -f "$MAILCOW/mailcow.conf" ]]; then
  DBROOT_PASS=$(grep -E '^DBROOT=' "$MAILCOW/mailcow.conf" | head -1 | cut -d= -f2- | tr -d '"' | tr -d "'")
fi
if [[ -z "$DBROOT_PASS" ]]; then
  echo "Could not read DBROOT from mailcow.conf" >&2
  exit 1
fi

mysql_mailcow() {
  docker exec -i mailcowdockerized-mysql-mailcow-1 mysql -uroot -p"${DBROOT_PASS}" "$@"
}

mysql_mailcow -e "CREATE DATABASE IF NOT EXISTS mdm_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'mdm_platform'@'%' IDENTIFIED BY '${DB_PASS}';
CREATE USER IF NOT EXISTS 'mdm_platform'@'172.%' IDENTIFIED BY '${DB_PASS}';
CREATE USER IF NOT EXISTS 'mdm_platform'@'127.0.0.1' IDENTIFIED BY '${DB_PASS}';
CREATE USER IF NOT EXISTS 'mdm_platform'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON mdm_platform.* TO 'mdm_platform'@'%';
GRANT ALL PRIVILEGES ON mdm_platform.* TO 'mdm_platform'@'172.%';
GRANT ALL PRIVILEGES ON mdm_platform.* TO 'mdm_platform'@'127.0.0.1';
GRANT ALL PRIVILEGES ON mdm_platform.* TO 'mdm_platform'@'localhost';
FLUSH PRIVILEGES;"

echo "==> Writing backend/.env"
mkdir -p "$ROOT/backend" "$LAB"/{bin,certs,depot,nanomdm-db}
if [[ ! -f "$ROOT/backend/.env" ]]; then
  cp "$ROOT/deploy/env.production.example" "$ROOT/backend/.env"
fi

# Fill secrets (idempotent-ish: only replace empty placeholders)
python3 - <<PY
from pathlib import Path
path = Path("$ROOT/backend/.env")
text = path.read_text()
replacements = {
    "DB_PASSWORD=": "DB_PASSWORD=${DB_PASS}",
    "NANO_MDM_API_KEY=": "NANO_MDM_API_KEY=${API_KEY}",
    "NANO_SCEP_CHALLENGE=": "NANO_SCEP_CHALLENGE=${SCEP_CHALLENGE}",
    "NANO_MDM_WEBHOOK_SECRET=": "NANO_MDM_WEBHOOK_SECRET=${WEBHOOK_SECRET}",
}
out = []
for line in text.splitlines():
    key = line.split("=", 1)[0] + "=" if "=" in line else None
    if key and key in replacements and (line == key or line.endswith("=") or line.split("=",1)[1] == ""):
        out.append(replacements[key])
    else:
        out.append(line)
path.write_text("\n".join(out) + "\n")
print("env updated")
PY

cd "$ROOT/backend"

echo "==> Composer + migrate"
composer install --no-dev --optimize-autoloader --no-interaction
if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
  php artisan key:generate --force
fi
php artisan migrate --force --seed
php artisan config:cache
php artisan route:cache

echo "==> Frontend build"
cd "$ROOT/frontend"
printf 'VITE_API_URL=/api/v1\n' > .env.production
npm ci
npm run build

echo "==> NanoMDM / SCEP Linux binaries"
mkdir -p "$LAB/bin"
cd /tmp
rm -rf /tmp/mdm-bin-dl
mkdir -p /tmp/mdm-bin-dl && cd /tmp/mdm-bin-dl

if [[ ! -x "$LAB/bin/nanomdm" ]]; then
  curl -fsSL -o nanomdm.zip "https://github.com/micromdm/nanomdm/releases/download/v0.9.0/nanomdm-linux-amd64-v0.9.0.zip"
  unzip -o nanomdm.zip
  BIN=$(find . -type f \( -name 'nanomdm' -o -name 'nanomdm-linux*' \) ! -name '*.zip' | head -1)
  cp "$BIN" "$LAB/bin/nanomdm"
  chmod +x "$LAB/bin/nanomdm"
fi

if [[ ! -x "$LAB/bin/scep" ]]; then
  curl -fsSL -o scep.zip "https://github.com/micromdm/scep/releases/download/v2.3.0/scepserver-linux-amd64-v2.3.0.zip"
  unzip -o scep.zip
  BIN=$(find . -type f -name 'scepserver*' | head -1)
  cp "$BIN" "$LAB/bin/scep"
  chmod +x "$LAB/bin/scep"
fi

"$LAB/bin/nanomdm" -version || "$LAB/bin/nanomdm" -help 2>&1 | head -2 || true
"$LAB/bin/scep" -h 2>&1 | head -3 || true

echo "==> Init CA if missing"
export NANOMDM_LAB="$LAB"
if [[ ! -f "$LAB/ca.pem" ]]; then
  if [[ -x "$ROOT/mdm-engine/scripts/init-ca.sh" ]]; then
    bash "$ROOT/mdm-engine/scripts/init-ca.sh"
  else
    mkdir -p "$LAB/depot"
    (cd "$LAB" && ./bin/scep ca -init -depot depot)
    cp -f "$LAB/depot/ca.pem" "$LAB/ca.pem"
  fi
fi
if [[ ! -f "$LAB/ca.pem" && -f "$LAB/depot/ca.pem" ]]; then
  cp -f "$LAB/depot/ca.pem" "$LAB/ca.pem"
fi

chmod +x "$ROOT/deploy/scripts/"*.sh
chown -R www-data:www-data "$ROOT"
# keep .env readable by www-data only
chmod 640 "$ROOT/backend/.env"
chown root:www-data "$ROOT/backend/.env"

echo "==> Host nginx site"
cp "$ROOT/deploy/nginx/mdm.wayda.co.tz.host.conf" /etc/nginx/sites-available/mdm.wayda.co.tz.conf
ln -sfn /etc/nginx/sites-available/mdm.wayda.co.tz.conf /etc/nginx/sites-enabled/mdm.wayda.co.tz.conf
nginx -t && systemctl reload nginx

echo "==> Mailcow edge + SAN"
cp "$ROOT/deploy/nginx/mdm.wayda.co.tz.mailcow.conf" "$MAILCOW/data/conf/nginx/mdm.wayda.co.tz.conf"
if ! grep -q 'mdm.wayda.co.tz' "$MAILCOW/mailcow.conf"; then
  sed -i 's/^ADDITIONAL_SAN=.*/&,mdm.wayda.co.tz/' "$MAILCOW/mailcow.conf"
fi
cd "$MAILCOW"
docker compose restart nginx-mailcow
# Trigger ACME renew/add SAN if possible
docker compose restart acme-mailcow || true

echo "==> systemd units"
cp "$ROOT/deploy/systemd/"*.service /etc/systemd/system/
systemctl daemon-reload
systemctl enable --now mdm-scep mdm-nanomdm mdm-laravel mdm-horizon

sleep 2
echo "==> Upload push cert if present"
if [[ -f "$LAB/certs/push.pem" && -f "$LAB/certs/push.key" ]]; then
  cat "$LAB/certs/push.pem" "$LAB/certs/push.key" \
    | curl -sS -T - -u "nanomdm:${API_KEY}" http://127.0.0.1:9000/v1/pushcert || true
fi

echo ""
echo "============================================"
echo "MDM deployed for https://${DOMAIN}"
echo "DB password stored in backend/.env"
echo "API key / SCEP challenge / webhook secret set in backend/.env"
echo ""
echo "REQUIRED DNS: ${DOMAIN} A → 161.97.182.204"
echo "After DNS + ACME: create a new enrollment in the UI."
echo "Login seed: superadmin@mdm.local / password (change immediately)"
echo "============================================"
