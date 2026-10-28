# Implementation Plan: 011-pot-node-v15-bringup

**Branch**: `011-pot-node-v15-bringup` | **Date**: 2026-10-28 | **Spec**: [spec.md](./spec.md)  
**Status**: Planned  
**Input**: Feature specification from `/specs/011-pot-node-v15-bringup/spec.md`

## Summary

Bring up the first outdoor V1.5 pot-node: print and hose-test committed STLs, bench-assemble per BOM/assembly docs, replace direct-MySQL firmware path with **HTTP-only ingest** (constitution), flash with local secrets, verify one end-to-end reading on the garden stack, then final lid close.

## Technical Context

**Language/Version**: Arduino C++ (ESP8266/NodeMCU), PHP 7.4+/8.x (ingest endpoint), MySQL 8 (storage only behind HTTP)  
**Primary Dependencies**: Existing `arduino/sketch_SoilSensor.ino`, garden stack (`public/` + `src/`), assembly/BOM docs, V1.5 STLs  
**Storage**: MySQL readings via server-side ingest; device config in EEPROM/local only  
**Testing**: Manual hose-test + multimeter + serial Modbus proof; HTTP ingest verified via logs/UI/DB; PHPUnit only if new PHP ingest code is added  
**Target Platform**: Bench + outdoor pot; LAN garden stack (bare-metal or Compose)  
**Project Type**: Hardware bring-up + thin firmware/API glue  
**Performance Goals**: One successful sample→HTTP→persist cycle; not a load test  
**Constraints**: HTTP-only ingest; no secrets in git; MySQL not on `0.0.0.0` for nodes; BOM/docs alignment; flash before final lid torque; **IP whitelist auth only**  
**Scale/Scope**: Single first node (PoC)

## Constitution Check

*GATE: Must pass before / during remediation; re-check after design edits.*

| Principle | Plan alignment |
|-----------|----------------|
| HTTP-only ingest; MySQL not on `0.0.0.0` | US3 delivers HTTP client + server ingest; acceptance forbids ESP→MySQL |
| Hardware changes need BOM/docs alignment | Prefer zero CAD change; any seal fix updates enclosure README + notes |
| No secrets in git | WiFi/HTTP credentials via local firmware config / EEPROM / server `.env` |
| Alfred-sized tasks | Tasks.md phases keep human print/assemble separate from code slices |
| LAN default | Ingest URL targets LAN host only |

**Known gap at plan time**: `sketch_SoilSensor.ino` still uses `MySQL_Connection`. Closing that gap is required work in this feature, not deferred to `012`.

## Feature Requirement Alignment *(new)*

The following FR distinctions from `spec.md` must be reflected in implementation:

| FR ID | Description | Implementation mapping |
|-------|-------------|------------------------|
| **FR-006** | System MUST ingest node readings over **HTTP only**; ESP MUST NOT require MySQL exposed on `0.0.0.0`. Authentication: LAN-trust-only (**IP whitelist only**). No offline queue. | T005 (contract), T006 (endpoint), T010 (firmware migration), SC-004 |
| **FR-007** | First successful bring-up MUST persist at least moisture (and EC/pH when available) so dashboard/summary can show the node. | T006/T007 (persist via existing models), SC-003 |
| **FR-008** | `sensors.last_reading` timestamp MUST be updated in the same successful ingest request before HTTP 2xx, else fail-closed with error logged if database unavailable. | T007 (timestamp update), FR-008 compliance check in T029 |


## Project Structure

### Documentation (this feature)

```text
specs/011-pot-node-v15-bringup/
├── plan.md              # This file
├── spec.md              # Refined specification
└── tasks.md             # Phased human + software tasks
```

### Source Code / artifacts (repository)

```text
hardware/enclosure/
├── pot_node_body_v1.stl
├── pot_node_lid_v1.stl
├── pot_node_gasket_v1.stl
├── pot_node_usb_cap_v1.stl
├── pot_node_enclosure_v1.scad
└── export_stls.sh

docs/setup/
├── HARDWARE_BOM.md
├── POT_NODE_ASSEMBLY.md
└── README.md                 # pin map / wiring overview

arduino/sketch_SoilSensor.ino # migrate off MySQL client → HTTP POST

public/                       # add or harden HTTP ingest entry (e.g. api/ingest or insertReading)
src/                          # optional service/model helpers for ingest + last_reading
```

**Structure Decision**: Reuse committed STLs and docs from `010`. Software work is limited to firmware ingest transport + a minimal authenticated/LAN HTTP ingest façade under `public/` (domain helpers in `src/` if non-trivial).

## Implementation Approach

### Phase A — Mechanical (human)

1. Print STLs (PETG body/lid, TPU gasket/cap); no `.scad` edit unless hose-test forces it.
2. Hose-test empty enclosure with gasket + USB cap seated; record pass/fail.
3. On fail: redress print/seal; only then consider minimal CAD fix + re-export STLs + doc note.

### Phase B — Electrical & flash (human + firmware config)

1. Follow `POT_NODE_ASSEMBLY.md` Phases A–D (power solder → logic Dupont → 5 V verify → Modbus serial).
2. Flash firmware **after** HTTP client work lands (or flash interim build only for Modbus bring-up, then reflash HTTP build before close).
3. Keep credentials out of git.

### Phase C — HTTP ingest (software)

1. **Server**: Minimal LAN HTTP endpoint accepting node readings (moisture required; temp/EC/pH optional fields), persisting via existing models/schema; update `sensors.last_reading` when applicable; reject unauthenticated/malformed payloads; **IP whitelist auth only**.
2. **Firmware**: Replace MySQL client usage with HTTP POST (or equivalent) to that endpoint; retain Modbus sampling; config fields for ingest URL / device identity instead of MySQL user/pass where possible.
3. **Verify**: One cycle → HTTP 2xx → reading appears in UI/DB within **5 minutes** of sample; confirm MySQL client not used in firmware build and server bind excludes `0.0.0.0` for node access.
4. **Docs**: Touch assembly/firmware notes if config steps change (WiFi + ingest URL).


### Phase D — Close-out (human)

1. Complete pre-close checklist; final torque; gland + USB cap.
2. Optional light second wet-check on sealed unit.
3. Short bring-up record (what passed, sensor ids, stack URL host only — no secrets).

## Risks / Rollback

| Risk | Mitigation / rollback |
|------|------------------------|
| Print/seal fail | Stop before electronics; reprint or redress gasket |
| Over-voltage on rail | Always meter 5.0 V before VIN/probe connect |
| RS485 silent | A/B swap, baud, 3V3 to XY-017 per BOM |
| HTTP path larger than expected | Keep endpoint minimal; **use IP whitelist only** — do not defer auth |
| Stack down during sample cycle | Fail-closed on HTTP 5xx/timeout; retry after server restart or network recovery; no offline ingest queue; confirm ingest within SC-003 window once stack is healthy |
| Firmware HTTP regresses Modbus | Keep serial Modbus proof as separate checkpoint before ingest test |
| Secrets leaked in commit | Pre-commit review; placeholders only in sketch defaults |

## Verification / Acceptance Mapping

| Success criterion | How verified |
|-------------------|--------------|
| SC-001 | Hose-test log / photo note |
| SC-002 | Multimeter + serial Modbus |
| SC-003 | Stack logs + UI/DB reading within **5 minutes** of sample |
| SC-004 | Firmware has no MySQL client path for acceptance build; server bind policy checked |
| SC-005 | Assembly pre-close checklist complete |

## Complexity Tracking

| Violation / stretch | Why needed | Simpler alternative rejected because |
|---------------------|------------|--------------------------------------|
| Firmware transport change inside "hardware bring-up" | Constitution + stated HTTP ingest goal; sketch is still MySQL | Accepting MySQL-from-ESP would violate constitution and SC-004 |

## Out of Scope (plan)

- Auto-watering, SMTP polish, dashboard redesign, RPi ARM64 stack (`012`).
- Full device fleet provisioning UX.
- Docker secrets migration (chmod/`env` hardening sufficient for this node).
