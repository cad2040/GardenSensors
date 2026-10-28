# Ingest Configuration Propagation Notes: Pot-Node V1.5

**Location**: `docs/api/ingest-config.md`  
**Status**: Draft (T013b)  
**Branch**: `011-pot-node-v15-bringup`  

## Purpose

This document describes how ingest configuration (URL, device ID, auth method) is versioned and propagated across firmware builds and BOM updates. It ensures no silent pin/BOM drift per FR-010.

## Configuration Fields

The following fields are configurable on the node (EEPROM or flash config) and server-side:

| Field | Example Value | Source | Notes |
|-------|---------------|--------|-------|
| `ingest_url` | `http://garden.local/public/api/ingest.php` | Server `.env` + LAN host | Must be reachable from node; no secrets in git |
| `device_id` | `XY-017-001` | Firmware config / EEPROM | Unique sensor identifier |
| `sensor_type` | `soil-probe-v1` | Firmware constant | Maps to DB schema |
| `auth_method` | `ip-whitelist` | Deployment policy | IP whitelist ONLY (no bearer tokens) |
| `retry_max` | `3` | Firmware constant | Max HTTP retries on 5xx/timeout |
| `retry_backoff_ms` | `1000` | Firmware constant | Base backoff for exponential retry |

## Versioning Strategy

- **Firmware**: Config fields stored in EEPROM or flash config section; updated via OTA or manual reflash.
- **Server**: Ingest URL and auth policy defined in `.env`; versioned in `docs/api/ingest-config.md`.
- **BOM**: No config fields tied to hardware parts; BOM updates documented separately.

## Propagation Mechanism

1. **Initial Flash**: Firmware defaults use placeholders (e.g., `http://localhost/api/ingest`). Builder replaces with LAN host before outdoor deploy.
2. **Reflash Trigger**: Any change to ingest endpoint schema or auth policy requires a firmware reflash.
3. **Documentation Update**: Changes to config fields are recorded here; `docs/setup/POT_NODE_ASSEMBLY.md` updated if flash steps change.

## Example Config Block (Firmware)

```c
// EEPROM/Flash config section — placeholders only
#define INGEST_URL         "http://garden.local/public/api/ingest.php"
#define DEVICE_ID          "XY-017-001"
#define SENSOR_TYPE        "soil-probe-v1"
#define AUTH_METHOD        "ip-whitelist"
#define RETRY_MAX          3
#define RETRY_BACKOFF_MS   1000
```

## Example Config Block (Server .env)

```env
# Server-side ingest configuration — no secrets in git
INGEST_HOST=garden.local
INGEST_PATH=/public/api/ingest.php
AUTH_METHOD=ip-whitelist
LAN_SUBNET=192.168.1.0/24
```

## Validation Checklist

- [ ] Firmware config placeholders present (no empty `#define` lines)
- [ ] Server `.env` has `INGEST_HOST`, `INGEST_PATH`, `AUTH_METHOD`, `LAN_SUBNET`
- [ ] `auth_method` in table is `ip-whitelist` only (not bearer for new nodes)
- [ ] No secrets (device_key, passwords) appear in either block