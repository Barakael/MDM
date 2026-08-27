# Custom MDM Engine

Placeholder for the production Apple MDM protocol server (Phase 8+).

While `MDM_ENGINE=nano`, Laravel talks to an external NanoMDM process via `NANO_MDM_BASE_URL`.

When ready, implement enrollment, check-in, APNs, and command processing here and point:

```env
MDM_ENGINE=custom
CUSTOM_MDM_BASE_URL=http://127.0.0.1:9001
```
