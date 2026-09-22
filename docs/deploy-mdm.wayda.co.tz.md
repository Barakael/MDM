# Deploy MDM Platform to mdm.wayda.co.tz

**Server:** `161.97.182.204`  
**Public URL:** `https://mdm.wayda.co.tz`

## Architecture

```text
Internet
  → Mailcow nginx :443 (TLS)
    → Host nginx :8088 (server_name mdm.wayda.co.tz)
      → /           frontend/dist
      → /api        Laravel :8010
      → /mdm/enroll Laravel enrollment download
      → /mdm        NanoMDM :9000
      → /scep       SCEP :8080
```

## DNS (required)

Create an **A record**:

| Name | Type | Value |
|------|------|-------|
| `mdm.wayda.co.tz` | A | `161.97.182.204` |

Without this, ACME/SSL and device enrollment will fail.

## One-command deploy (from your Mac)

```bash
bash deploy/scripts/deploy-to-server.sh
```

This rsyncs the repo, copies `~/nanomdm-lab/certs/push.{pem,key}` if present, and runs `bootstrap-server.sh` on the VPS.

## After deploy

1. Wait for DNS + Mailcow ACME (cert may take a few minutes after `ADDITIONAL_SAN` includes `mdm.wayda.co.tz`).
2. Open https://mdm.wayda.co.tz — login `superadmin@mdm.local` / `password` (change immediately).
3. Confirm Dashboard shows NanoMDM **Connected**.
4. **Enrollment** → create profile → install on device (same Wi‑Fi not required once HTTPS works).
5. Device appears automatically → use **Remote control**.

## Secrets

Generated on first bootstrap into `backend/.env`:

- MySQL password (mailcow MariaDB `:13306`)
- `NANO_MDM_API_KEY`
- `NANO_SCEP_CHALLENGE`
- `NANO_MDM_WEBHOOK_SECRET`

Push topic is pre-set to your Apple cert topic. Re-upload push cert anytime:

```bash
ssh root@161.97.182.204
cd /var/www/mdm.wayda.co.tz
source backend/.env
cat mdm-lab/certs/push.pem mdm-lab/certs/push.key \
  | curl -T - -u "nanomdm:${NANO_MDM_API_KEY}" http://127.0.0.1:9000/v1/pushcert
```

## Services

```bash
systemctl status mdm-laravel mdm-horizon mdm-nanomdm mdm-scep
journalctl -u mdm-nanomdm -f
```
