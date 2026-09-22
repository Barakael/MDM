# Custom MDM Engine

Placeholder for the production Apple MDM protocol server (Phase 8+).

## NanoMDM (current engine)

Local lab: `~/nanomdm-lab`

```bash
# Terminal 1
bash mdm-engine/scripts/start-scep.sh

# Terminal 2
bash mdm-engine/scripts/start-nanomdm.sh
```

Full guide: [docs/nanomdm-setup.md](../docs/nanomdm-setup.md)

Laravel connects via `NANO_MDM_*` in `backend/.env` (API base URL, public URL, SCEP, topic, webhook). Export the same `NANO_MDM_WEBHOOK_URL` when starting NanoMDM so check-ins upsert devices automatically.

When ready for custom engine:

```env
MDM_ENGINE=custom
CUSTOM_MDM_BASE_URL=http://127.0.0.1:9001
```
