# MDM Platform — Local Setup (no Docker)

## Prerequisites

- PHP 8.3+ with extensions: mbstring, openssl, pdo_mysql, redis (or predis), tokenizer, xml, curl
- Composer 2
- Node.js 20+
- MySQL 8+
- Redis 7+
- NanoMDM running locally (for MDM_ENGINE=nano)

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

In another terminal (queue workers):

```bash
cd backend
php artisan horizon
```

## 3. Frontend

```bash
cd frontend
npm install
npm run dev            # http://localhost:5173
```

## 4. MDM engine (.env switch)

In `backend/.env`:

```env
MDM_ENGINE=nano
NANO_MDM_BASE_URL=http://127.0.0.1:9000
NANO_MDM_API_KEY=
```

Switch to custom later with `MDM_ENGINE=custom` and `CUSTOM_MDM_*`. Per-device override: set `devices.mdm_engine`.

## 5. Default users (after seed)

| Email | Password | Role |
|-------|----------|------|
| superadmin@mdm.local | password | Super_Admin |
| admin@mdm.local | password | Admin |

## Environments

Use `.env` only — `local`, `staging`, `production` via `APP_ENV`. No Docker.
