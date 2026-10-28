# Tasks: 011-pot-node-v15-bringup

**Input**: Design documents from `/specs/011-pot-node-v15-bringup/`  
**Prerequisites**: `plan.md`, `spec.md`  
**Status**: Planned  
**Tests**: Manual hardware verification required; PHPUnit only for new PHP ingest code.

**Organization**: Phases follow user stories. Human/print tasks are Alfred-stop gates — do not auto-mark complete without operator confirmation.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files / people, no dependencies)
- **[Story]**: US1…US4 mapping
- Paths are repo-relative

---

## Phase 1: Setup (Shared)

**Purpose**: Confirm artifacts and active feature before spending filament or BOM.

- [ ] T001 Confirm active Spec Kit feature points at `specs/011-pot-node-v15-bringup` (`.specify/feature.json`)
- [ ] T002 [P] Inventory committed STLs in `hardware/enclosure/` (body, lid, gasket, USB cap)
- [ ] T003 [P] Review `docs/setup/HARDWARE_BOM.md` and `docs/setup/POT_NODE_ASSEMBLY.md` against parts on hand
- [ ] T004 [P] Confirm garden stack can be brought up on LAN (`sudo ./setup.sh local` or Compose) for later US3

**Checkpoint**: Parts + docs + stack path understood; no CAD redesign planned unless hose-test fails

---

## Phase 2: Foundational — HTTP ingest contract (Blocking for US3)

**Purpose**: Define and implement the constitution-compliant ingest path **before** treating firmware flash as final. Modbus bench work (US2) can proceed in parallel once power is safe, but **acceptance flash** depends on this phase.

**⚠️ CRITICAL**: Do not accept bring-up complete while ESP still requires direct MySQL.

- [ ] T005 Document target HTTP ingest contract including authentication method (**IP whitelist only**), request shape (fields: sensor id(s), moisture, optional temp/EC/pH, timestamp/device id), and error handling policy in a short note under `docs/api/ingest-contract.md` (no secrets)
- [ ] T006 Implement minimal LAN HTTP ingest endpoint under `public/` (new thin script or API) that validates input and persists readings via existing DB/models
- [ ] T007 [P] On successful ingest, update `sensors.last_reading` (and battery if provided) consistent with schema
- [ ] T008 [P] Ensure MySQL remains unreachable from nodes (no `0.0.0.0` MySQL publish for this path); document LAN URL pattern only
- [ ] T009 Add or extend PHPUnit coverage for ingest validation/persist happy-path and reject malformed payloads
- [ ] T010 Migrate `arduino/sketch_SoilSensor.ino` from `MySQL_Connection` to HTTP client POST with retry policy (max 3 retries on 5xx/timeout, exponential backoff), error logging for 4xx/5xx responses; keep Modbus sampling
- [ ] T011 Replace committed placeholder MySQL credential fields with ingest URL / device config placeholders; **no real secrets in git**
- [ ] T012 Update assembly/firmware notes if flash/config steps change (`docs/setup/POT_NODE_ASSEMBLY.md` and/or setup README pointer)

**Checkpoint**: HTTP ingest works with a mocked or serial-driven test POST; firmware builds with HTTP transport

---

## Phase 3: User Story 1 — Print & hose-test (Priority: P1) 🎯

**Goal**: Printed V1.5 enclosure passes empty-case hose-test  
**Independent Test**: Hose-test with gasket + USB cap; no water in cavity

- [ ] T013 [US1] Print `hardware/enclosure/pot_node_body_v1.stl` in PETG
- [ ] T014 [P] [US1] Print `hardware/enclosure/pot_node_lid_v1.stl` in PETG
- [ ] T015 [P] [US1] Print `hardware/enclosure/pot_node_gasket_v1.stl` in TPU
- [ ] T016 [P] [US1] Print `hardware/enclosure/pot_node_usb_cap_v1.stl` in TPU
- [ ] T017 [US1] Dry-fit body, gasket, lid, USB cap; note any seal-face issues
- [ ] T018 [US1] Hose-test empty enclosure with USB-C cap seated; record pass/fail
- [ ] T019 [US1] If fail: redress print/seal or minimal CAD fix + `export_stls.sh` + doc note (stop electronics until pass)

**Checkpoint**: US1 pass required before stuffing BOM electronics into the case

---

## Phase 4: User Story 2 — Bench assembly & Modbus proof (Priority: P1)

**Goal**: Electrical node reads probe on serial; 5.0 V rail correct  
**Independent Test**: Multimeter + serial Modbus metrics

- [ ] T020 [US2] Build power chain on bench (18650 → TP4056 → MT3608); trim to **5.00 V ± 0.05 V** before VIN/probe connect per `POT_NODE_ASSEMBLY.md`
- [ ] T021 [US2] Wire NodeMCU ↔ XY-017 (3V3, GND, D6/D7) and D0–RST; probe on **5 V** rail not 3V3
- [ ] T022 [US2] Flash bring-up firmware build; configure **local-only** WiFi/HTTP settings (not committed)
- [ ] T023 [US2] Confirm serial/Modbus moisture (and EC/pH if available); fix A/B or baud if silent
- [ ] T024 [US2] Install into V1.5 cavity in documented mechanical order (floor boards before TP4056 shelf blocks access); lid not finally torqued

**Checkpoint**: US2 electrical + Modbus proof done; lid still serviceable

---

## Phase 5: User Story 3 — First HTTP ingest (Priority: P1) 🎯 MVP complete when done

**Goal**: One sample cycle persists via HTTP on the garden stack  
**Independent Test**: HTTP 2xx + reading visible in UI/DB; no ESP MySQL session

- [ ] T025 [US3] Deploy/start garden stack on LAN with ingest endpoint available
- [ ] T026 [US3] Point node local config at LAN ingest URL and sensor ids
- [ ] T027 [US3] Run one wake/sample cycle; confirm HTTP success in serial/server logs
- [ ] T028 [US3] Confirm reading(s) visible in dashboard/API or DB for that sensor
- [ ] T029 [US3] Confirm acceptance build does not use ESP→MySQL (verify no `MySQL_Connection` in firmware); stack MySQL not published for node use (`0.0.0.0` bind excluded)
- [ ] T037 [P] [US3] Verify sketch has no MySQL_Connection (HTTP+Modbus only)  #domain:arduino-sensor-firmware
- [ ] T038 Document/maintain docs/api/ingest-config.md (URL, device id, versioning)  #domain:iot-core-ingest

**Checkpoint**: SC-003 / SC-004 satisfied — safe to final-close

---

## Phase 6: User Story 4 — Final close (Priority: P2)

**Goal**: Outdoor-ready sealed node after ingest proof  
**Independent Test**: Pre-close checklist 100% + lid torque

- [ ] T030 [US4] Complete `POT_NODE_ASSEMBLY.md` pre-close checklist
- [ ] T031 [US4] Torque lid evenly; seal M12 gland; seat USB cap
- [ ] T032 [P] [US4] Optional light second wet-check on sealed unit
- [ ] T033 [US4] Record short bring-up note (pass/fail, sensor ids, stack host — **no passwords**)

**Checkpoint**: Feature bring-up complete for node #1

---

## Phase 7: Polish & Cross-Cutting

- [ ] T034 [P] If ingest docs were added, link them from `docs/setup/README.md` or `docs/api/`
- [ ] T035 Re-run Spec Kit analyze mindset: ensure tasks still map to FR-001…FR-010
- [ ] T036 Human review stop — do not mark physical tasks done in git without operator confirmation

---

## Dependencies & Execution Order

### Phase dependencies

- **Phase 1** → no deps
- **Phase 2** → after T001–T004 planning; **blocks US3 acceptance** (and final flash for close)
- **Phase 3 (US1)** → after T002; **blocks cavity stuff** (T024)
- **Phase 4 (US2)** → after US1 pass (for in-case) and T020 power safety; Modbus can use interim firmware but final flash needs Phase 2
- **Phase 5 (US3)** → Phase 2 + US2 Modbus proof + stack up
- **Phase 6 (US4)** → US3 success
- **Phase 7** → after US3 (docs) / parallel notes OK

### Parallel opportunities

- T013–T016 prints in parallel
- T006–T009 server work parallel with T010–T011 firmware once contract (T005) exists
- T032 optional wet-check parallel with T033 notes

### MVP (minimum ship for "bring-up done")

US1 hose-test pass + US2 Modbus proof + US3 first HTTP ingest. US4 required before outdoor leave-behind.

---

## Notes

- Stop for human review after US1, after US2, and after US3 before final torque.
- `#domain:iot-core-ingest` for T005–T008,T029; `#domain:iot-garden-php` for ingest endpoint tasks; hardware steps are human-executed.
- `#domain:arduino-sensor-firmware` for T010–T011,T037 (verify no MySQL_Connection).
- Do not merge FR-007/008 — keep separate.
- Do not mark [ ] complete without operator confirmation.