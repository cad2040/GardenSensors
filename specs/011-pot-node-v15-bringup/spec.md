# Feature Specification: Pot-Node V1.5 Hardware Bring-Up

**Feature Branch**: `011-pot-node-v15-bringup`  
**Created**: 2026-10-23  
**Status**: Planned  
**Input**: Physical bring-up of the first V1.5 outdoor pot-node from committed STLs through first successful HTTP sensor ingest.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Print and hose-test the V1.5 enclosure (Priority: P1)

A builder prints the committed STLs (PETG body/lid, TPU gasket/USB cap), assembles the empty enclosure with gasket and USB cap, and hose-tests for water ingress before stuffing electronics.

**Why this priority**: A leaking case wastes BOM parts and blocks outdoor deployment; mechanical seal must pass before assembly investment.

**Independent Test**: Print all four STLs, seat gasket + USB cap, hose-test; pass only if no water reaches the cavity interior.

**Acceptance Scenarios**:

1. **Given** STLs in `hardware/enclosure/`, **When** printed in PETG (body/lid) and TPU (gasket/USB cap), **Then** parts seat together without forcing or filing critical seal faces.
2. **Given** gasket and USB cap installed, **When** hose-tested with USB-C cap seated, **Then** no water enters the cavity (pass) or leaks are logged with location (fail → fix print/seal before electronics).

---

### User Story 2 - Bench-assemble and flash the pot-node (Priority: P1)

A builder follows `POT_NODE_ASSEMBLY.md` and `HARDWARE_BOM.md`: solder power chain on the bench, Dupont logic wiring, verify 5.00 V, flash firmware **before** final lid torque, confirm Modbus probe reads on serial.

**Why this priority**: Correct electrical bring-up is the gate before enclosure close and network ingest.

**Independent Test**: Complete Phases A–D of the assembly guide; serial shows moisture/temp/EC/pH (or documented probe subset); MT3608 holds 5.00 V ± 0.05 V under load.

**Acceptance Scenarios**:

1. **Given** locked BOM parts, **When** power chain is built per guide, **Then** MT3608 output is trimmed to **5.00 V ± 0.05 V** before NodeMCU/probe are connected.
2. **Given** NodeMCU + XY-017 + probe wired, **When** firmware is flashed with **local-only** WiFi/HTTP secrets, **Then** serial/Modbus path reports probe metrics without powering the probe from 3V3.
3. **Given** bench electrical success, **When** mechanical install into V1.5 cavity proceeds, **Then** install order matches the assembly guide (floor boards before TP4056/MT3608 blocking access).

---

### User Story 3 - First HTTP ingest to the garden stack (Priority: P1)

The node sends readings over **HTTP only** to the garden stack on the LAN. MySQL is not exposed on `0.0.0.0`. An operator confirms a new reading in DB/UI/logs after one wake/sample cycle.

**Why this priority**: Constitution mandates HTTP-only ingest; current sketch still uses direct MySQL — bring-up is incomplete until HTTP path works end-to-end.

**Independent Test**: With garden stack running (local or Compose), trigger one sample cycle; observe HTTP request success and persisted reading(s) for the configured sensor id(s); confirm MySQL port is not published WAN-wide.

**Acceptance Scenarios**:

1. **Given** a running garden stack and configured node, **When** the node completes a sample cycle, **Then** readings arrive via HTTP ingest (not a direct MySQL client session from the ESP).
2. **Given** a successful ingest, **When** the operator checks dashboard/API or DB, **Then** moisture (and EC/pH when probe provides them) are visible for that sensor/plant linkage.
3. **Given** WiFi or HTTP credentials, **When** firmware/config is stored, **Then** secrets remain off git (EEPROM/local config / `.env` on server only).

---

### User Story 4 - Close lid and outdoor-ready checklist (Priority: P2)

After ingest is verified on the bench or with lid loosely fitted, the builder completes final torque, gland seal, and USB cap, then records a short bring-up note.

**Why this priority**: Prevents sealed-box debug; close only after software/electrical proof.

**Independent Test**: Pre-close checklist in `POT_NODE_ASSEMBLY.md` fully checked; lid torqued; optional second hose-check on sealed unit (gentle).

**Acceptance Scenarios**:

1. **Given** HTTP ingest verified, **When** lid is torqued and USB cap seated, **Then** gland is sealed and no bare 18650 shorts exist.
2. **Given** closed node, **When** operator reviews checklist, **Then** flash-before-final-torque and HTTP ingest checks are marked done.

---

### Edge Cases

- Print warp / poor TPU seal → hose-test fails; reprint or redress gasket before electronics.
- MT3608 drifts under load → re-trim; do not leave probe/MCU on over-voltage.
- RS485 silent → swap A/B, verify baud (4800/9600), confirm XY-017 on 3V3.
- WiFi join fails → node MUST NOT fall back to MySQL; fail closed (log + retry WiFi with bounded attempts per firmware policy; no alternate DB transport).
- HTTP ingest 4xx/5xx → do not treat as success; log status on serial; retry only transient 5xx/timeouts with bounded backoff; do not retry 4xx without config fix.
- Stack down → bring-up must fail-closed on HTTP 5xx/timeout; retry only after server-side restart or network recovery; acceptance requires successful ingest within 5 minutes of stack restoration.
- Deep-sleep brownout → verify D0–RST bridge and battery under WiFi peak.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Builder MUST be able to print body, lid, gasket, and USB-cap STLs from `hardware/enclosure/` without CAD changes for this bring-up.
- **FR-002**: Empty enclosure MUST pass a hose-test with gasket and USB-C cap seated before electronics install.
- **FR-003**: Assembly MUST follow `docs/setup/POT_NODE_ASSEMBLY.md` and locked parts in `docs/setup/HARDWARE_BOM.md`.
- **FR-004**: Power rail MUST be MT3608-regulated **5.0 V** shared by NodeMCU `VIN` and probe VCC; probe MUST NOT be powered from ESP 3V3.
- **FR-005**: Firmware (`arduino/sketch_SoilSensor.ino`) MUST be flashable before final lid torque; local WiFi/HTTP config MUST NOT be committed.
- **FR-006**: System MUST ingest node readings over **HTTP only** (constitution); ESP MUST NOT require MySQL exposed on `0.0.0.0`. Authentication: LAN-trust-only (IP whitelist only). No offline queue.
- **FR-007**: First successful bring-up MUST persist at least moisture (and EC/pH when available) so dashboard/summary can show the node.
- **FR-008**: `sensors.last_reading` timestamp MUST be updated in the same successful ingest request before HTTP 2xx, else fail-closed with error logged if database unavailable.
- **FR-009**: Pre-close checklist items in the assembly guide MUST be satisfiable for this build.
- **FR-010**: Any firmware or ingest-endpoint change required for HTTP MUST be documented in a versioned config note under `docs/api/` before implementation, ensuring no silent pin/BOM drift.

### Key Entities

- **Pot-node (V1.5)**: Printed enclosure + locked BOM electronics + RS485 probe.
- **Reading**: Timestamped metric(s) for a sensor id delivered via HTTP ingest.
- **Garden stack**: LAN-deployed PHP/MySQL (and optional Compose) receiving ingest — MySQL not WAN-published for node access.
- **Local device config**: WiFi SSID/password, ingest URL, device/sensor ids — device-local or server `.env`, never git.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Four STLs printed and empty enclosure hose-test completed with pass/fail recorded within one build session after prints cool.
- **SC-002**: Bench electrical bring-up reaches stable **5.00 V ± 0.05 V** and Modbus metrics on serial before enclosure close.
- **SC-003**: One wake/sample cycle produces ≥1 HTTP-accepted reading visible in stack UI or DB within 5 minutes of a healthy LAN path.
- **SC-004**: No MySQL port is required to be reachable from the NodeMCU for acceptance (HTTP path only).
- **SC-005**: Final pre-close checklist is 100% checked before outdoor placement.

## Assumptions

- Committed V1.5 STLs and assembly/BOM docs from feature `010` are the print/build source of truth (no OpenSCAD redesign in this feature unless hose-test forces a documented fix).
- Builder has PETG + TPU filament, multimeter, and locked BOM parts on hand.
- Garden stack can be reached on LAN via `setup.sh local`, Compose, or equivalent.
- Current sketch still speaks MySQL; migrating (or adding) HTTP ingest client + server endpoint is **in scope** for US3.
- SMTP polish and auto-watering remain out of scope.
- RPi controller work belongs to `012`, not this feature.

## Out of Scope

- Priority B auto-watering / pump automation.
- Docker thin-installer polish beyond what’s needed to have a reachable stack.
- New dashboard UI redesign.
- Enclosure CAD redesign except minimal seal fixes proven by hose-test failure.
- Native Win/Mac installers.

