# MDM Platform

Laravel API + React console for Apple MDM, with engine switching via `.env` (`MDM_ENGINE=nano|custom`). No Docker.

## Quick start

See [docs/local-setup.md](docs/local-setup.md).

```bash
# Backend
cd backend && composer install && cp .env.example .env && php artisan key:generate
# configure MySQL/Redis in .env, then:
php artisan migrate --seed
php artisan serve
php artisan horizon   # separate terminal

# Frontend
cd frontend && npm install && npm run dev
```

## Defaults after seed

- `superadmin@mdm.local` / `password` (Super_Admin)
- `admin@mdm.local` / `password` (Admin, Acme Corp)
- Sample device: iPhone 11

## MDM connectivity

```env
MDM_ENGINE=nano
NANO_MDM_BASE_URL=http://127.0.0.1:9000
```

Per-device override: `devices.mdm_engine`.
