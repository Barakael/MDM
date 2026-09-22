# NanoMDM local server setup

Lab directory: `~/nanomdm-lab` (override with `NANOMDM_LAB`).

All public hosts are driven by **one `.env` block** in `backend/.env` (mirrored in root `.env.example`). Use LAN IP / ngrok today; flip to HTTPS domains after deploy.

## 1. Install binaries

```bash
mkdir -p ~/nanomdm-lab/bin
cp ~/Downloads/nanomdm-darwin-amd64-v0.9.0/nanomdm-darwin-amd64 ~/nanomdm-lab/bin/nanomdm
xattr -cr ~/nanomdm-lab/bin/nanomdm
chmod +x ~/nanomdm-lab/bin/nanomdm
```

Install SCEP **server** (not scepclient):

```bash
bash mdm-engine/scripts/install-scep.sh
bash mdm-engine/scripts/init-ca.sh
```

## 2. Configure `backend/.env` (single switch)

```env
MDM_ENGINE=nano

# Server-to-server (can stay localhost when Nano + Laravel share the Mac)
NANO_MDM_BASE_URL=http://127.0.0.1:9000
NANO_MDM_API_KEY=nanomdm

# Device-facing (LAN IP / ngrok now → HTTPS later)
APP_URL=http://192.168.x.x:8000
NANO_MDM_PUBLIC_URL=http://192.168.x.x:9000
NANO_SCEP_URL=http://192.168.x.x:8080/scep
NANO_SCEP_CHALLENGE=nanomdm
NANO_MDM_TOPIC=

# NanoMDM → Laravel (start-nanomdm.sh reads these)
NANO_MDM_WEBHOOK_URL=http://127.0.0.1:8000/api/v1/webhooks/nanomdm
NANO_MDM_WEBHOOK_SECRET=
NANO_MDM_DEFAULT_ORGANIZATION_ID=1
```

Frontend (`frontend/.env`):

```env
VITE_API_URL=http://192.168.x.x:8000/api/v1
```

After pushcert upload, set `NANO_MDM_TOPIC` to the returned topic (e.g. `com.apple.mgmt.External.…`).

### Deploy switch (HTTPS)

Change only:

- `APP_URL`
- `NANO_MDM_PUBLIC_URL`
- `NANO_SCEP_URL`
- `NANO_MDM_WEBHOOK_URL`
- `VITE_API_URL`

Then: rebuild frontend, restart NanoMDM (picks up webhook URL), **create new enrollment profiles** (old profiles still point at the old public URL).

## 3. Start services

**SCEP**

```bash
bash mdm-engine/scripts/start-scep.sh
```

**NanoMDM** (exports webhook from the same env names):

```bash
export NANO_MDM_API_KEY=nanomdm
export NANO_MDM_WEBHOOK_URL=http://127.0.0.1:8000/api/v1/webhooks/nanomdm
# optional: export NANO_MDM_WEBHOOK_SECRET=...
bash mdm-engine/scripts/start-nanomdm.sh
```

Expose device-facing ports with ngrok or LAN firewall as needed (`NANO_MDM_PUBLIC_URL` / `NANO_SCEP_URL` must match what phones can reach).

**Laravel + Horizon**

```bash
cd backend && php artisan serve
cd backend && php artisan horizon   # required for MDM command queue
```

## 4. APNs push certificate

```bash
bash mdm-engine/scripts/upload-push-cert.sh
# or:
# cat ~/nanomdm-lab/certs/push.pem ~/nanomdm-lab/certs/push.key \
#   | curl -T - -u nanomdm:nanomdm http://127.0.0.1:9000/v1/pushcert
```

Copy the returned `topic` into `NANO_MDM_TOPIC` in `.env`.

## 5. Enroll from the console

1. Open **Enrollment** in the UI — it shows configured ServerURL / SCEP / Topic from `.env`.
2. Create a Configurator enrollment (builds a real MDM+SCEP profile with `?enrollment_token=`).
3. Install the profile on the device (`/mdm/enroll/{token}` download).
4. Device **appears automatically** in Devices (NanoMDM webhook → Laravel DB). No manual UDID.
5. Lock / Refresh from the device page; command status updates via webhook.

## 6. Verify

```bash
curl -s -u nanomdm:nanomdm http://127.0.0.1:9000/version
# Logged-in API:
# GET /api/v1/mdm/status  → reachable, urls, last_webhook_at
```

Closed loop checklist:

1. SCEP + NanoMDM with `NANO_MDM_WEBHOOK_URL` → Laravel  
2. Push cert uploaded; `NANO_MDM_TOPIC` set  
3. Create enrollment in UI → install on device  
4. Device appears without manual UDID  
5. Lock from UI → command moves to acknowledged  
