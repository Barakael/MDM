# MDM Platform — Local Setup (no Docker)

## Prerequisites

- PHP 8.3+ with extensions: mbstring, openssl, pdo_mysql, redis (or predis), tokenizer, xml, curl
- Composer 2
- Node.js 20+
- MySQL 8+
- Redis 7+
- NanoMDM + SCEP running locally (for `MDM_ENGINE=nano`)

See [nanomdm-setup.md](nanomdm-setup.md) for SCEP + NanoMDM + webhook + enrollment.

## 1. Database

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS mdm_platform;"
```

## 2. Backend

```bash
cd backend
cp .env.example .env   # or merge from repo root .env.example
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve      # http://localhost:8000
```

In another terminal (queue workers — **required** for lock/refresh/erase):

```bash
cd backend
php artisan horizon
```

## 3. Frontend

```bash
cd frontend
cp .env.example .env   # set VITE_API_URL to your LAN IP or leave /api/v1 with Vite proxy
npm install
npm run dev            # http://localhost:5173
```

## 4. MDM endpoints (.env — one place to switch)

In `backend/.env`:

```env
MDM_ENGINE=nano

# Laravel ↔ NanoMDM API (internal)
NANO_MDM_BASE_URL=http://127.0.0.1:9000
NANO_MDM_API_KEY=nanomdm

# Device-facing (LAN IP today → HTTPS domain after deploy)
APP_URL=http://192.168.x.x:8000
NANO_MDM_PUBLIC_URL=http://192.168.x.x:9000
NANO_SCEP_URL=http://192.168.x.x:8080/scep
NANO_SCEP_CHALLENGE=nanomdm
NANO_MDM_TOPIC=com.apple.mgmt.External.…

# NanoMDM → Laravel webhook
NANO_MDM_WEBHOOK_URL=http://127.0.0.1:8000/api/v1/webhooks/nanomdm
NANO_MDM_WEBHOOK_SECRET=
NANO_MDM_DEFAULT_ORGANIZATION_ID=1
```

Frontend:

```env
VITE_API_URL=http://192.168.x.x:8000/api/v1
```

**After deploy:** change `APP_URL`, `NANO_MDM_PUBLIC_URL`, `NANO_SCEP_URL`, `NANO_MDM_WEBHOOK_URL`, `VITE_API_URL` to HTTPS domains → rebuild frontend → restart NanoMDM → create **new** enrollments.

Dashboard → MDM status banner shows whether NanoMDM is reachable and which URLs are configured.

## 5. Default users (after seed)

| Email | Password | Role |
|-------|----------|------|
| superadmin@mdm.local | password | Super_Admin |
| admin@mdm.local | password | Admin |

## Environments

Use `.env` only — `local`, `staging`, `production` via `APP_ENV`. No Docker.
