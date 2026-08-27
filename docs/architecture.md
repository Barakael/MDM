# Architecture notes

## Boundaries

- `app.*` — React SPA + Laravel `/api/v1` (Sanctum)
- `mdm.*` — Apple device endpoints (`/mdm/checkin`, `/mdm/connect`, `/mdm/enroll/{token}`)

Locally both can share one Laravel host.

## Engine switch

`.env` `MDM_ENGINE` is the global default. `devices.mdm_engine` overrides per device for Nano → Custom migration.

Laravel never talks to APNs directly for command delivery when using NanoMDM; `NanoMdmEngine` HTTP-calls `NANO_MDM_BASE_URL`.
