# Tasks: 011-rpi-controller-node

**Input**: Design documents from `/specs/011-rpi-controller-node/`  
**Prerequisites**: `plan.md`, `spec.md`  
**Status**: Planned  
**Tests**: Compose healthchecks + LAN GUI probe; ARM64 host or documented ARM64 CI/VM; human Pi deploy gate.

**Organization**: Phases follow user stories. Physical Pi deploy is a human gate — do not auto-mark complete without operator confirmation.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files / people)
- **[Story]**: US1…US4 mapping
- Paths are repo-relative

---

## Phase 1: Setup (Shared)

- [ ] T001 Confirm active Spec Kit feature points at `specs/011-rpi-controller-node` (`.specify/feature.json`)
- [ ] T002 [P] Inventory baseline paths: `docker-compose.yml`, `scripts/docker-install.sh`, `docs/setup/docker.md`, `docker/Dockerfile.php`

**Checkpoint**: Active feature + paths known

---

## Phase 2: US1 — ARM64 platform path

**Goal**: Pi can pull/build and start stack on `linux/arm64`  
**Independent Test**: manifest/platform checks + compose config validation

- [ ] T003 (#domain:docker-ufw) Verify ARM64 manifests for images used by `web`/`db` (`docker manifest inspect` / documented pull with `--platform linux/arm64`); note blockers in `docs/setup/rpi-controller.md` if any
- [ ] T004 (#domain:safe-script-production) Create `arm64/docker-compose.yml` (or compose override chain) setting `platform: linux/arm64` where required for build/pull — do not break root Desktop compose defaults
- [ ] T005 (#domain:safe-script-production) Extend `scripts/docker-install.sh` with `--target-platform=linux/arm64` and/or `uname -m` detect; fail-closed with clear message when ARM64 required but host is not; default path remains current Desktop behavior
- [ ] T006 [P] (#domain:iot-garden-php) Ensure `setup.sh` docker mode docs or flags mention Pi/`arm64` file chain (pointer OK if install script is primary)

**Checkpoint**: ARM64 compose file + installer flag exist on disk

---

## Phase 3: US4 — Absolute memory + SD-friendly mounts (`arm64/` only)

**Goal**: Absolute mem caps in `arm64/` — **not** `--memory=50%` as Pi SoT  
**Independent Test**: `arm64/docker-compose.yml` shows numeric mem limits for `web` and `db`

- [ ] T007 (#domain:docker-ufw) Add absolute memory limits in `arm64/docker-compose.yml` for **4 GB Pi class**: `web` `mem_limit: 768m`, `db` `mem_limit: 1536m` (comment + `docs/setup/rpi-controller.md`; raise only with rationale)
- [ ] T008 [P] (#domain:docker-ufw) Add cheap SD mitigations in `arm64/` only where safe (tmpfs for ephemeral caches / read-only root for stateless sidecars if applicable) — no app code rewrite

**Checkpoint**: Pi override owns resource envelope; root compose unchanged for Desktop mem folklore

---

## Phase 4: US2/US3 — LAN GUI + MySQL not a node path + UFW

**Goal**: LAN browser reaches GUI; MySQL not on `0.0.0.0` for nodes  
**Independent Test**: Port publish review + doc’d UFW snippet

- [ ] T009 (#domain:docker-ufw) In `arm64/` publish posture: web port for LAN GUI; DB either omit host ports or bind `127.0.0.1` only — confirm compose YAML has no DB publish on `0.0.0.0` / bare `3306:3306`
- [ ] T010 (#domain:docker-ufw) Add UFW/`DOCKER-USER` guidance under `docs/setup/rpi-controller.md` (or `docs/setup/docker.md` cross-link): LAN→web HTTP allow, WAN drop, **MySQL not in LAN allowlist** (`#domain:docker-ufw`)
- [ ] T011 (#domain:safe-script-production) Harden `scripts/docker-install.sh` so `.env` and `docker/first-run/answers.env` are `chmod 600` after create/update; verify with `stat -c '%a'` (or equivalent) before marking complete

**Checkpoint**: Publish + secrets posture match FR-004…FR-007

---

## Phase 5: Docs + human verify (US1–US3)

**Goal**: Operator can deploy on Pi and prove SC-001…SC-006  
**Independent Test**: Human gate on real Pi (or ARM64 stand-in)

- [ ] T012 (#domain:iot-garden-php) Write `docs/setup/rpi-controller.md`: prerequisites, install command (`--target-platform=linux/arm64` / `arm64/` compose files), LAN GUI URL, **future ingest URL for 012** `http://<pi-lan>:<port>/api/ingest.php`, note that `/mnt/SDD1` and SD wear monitors are optional/out of v1 (FR-009), 4 GB SoT mem caps (`768m`/`1536m`), link from `docs/setup/docker.md`
- [ ] T013 (#domain:iot-core-ingest) In that doc, state explicitly: pot nodes use HTTP ingest only; MySQL is not the node write path (point at `docs/api/ingest-contract.md` if present)
- [ ] T014 Human gate: deploy on ARM64 Pi; confirm healthchecks; GUI from second LAN device; MySQL not LAN-reachable as node path; record result in bring-up note (no secrets). Do not mark complete in git without operator confirmation.
- [ ] T015 [P] Mark FR/SC coverage in a short checklist comment at bottom of `docs/setup/rpi-controller.md` (SC-001…SC-006)

**Checkpoint**: Feature acceptance — do not start `012` US3 HTTP proof until T014 passes

---

## Phase 6: Polish

- [ ] T016 Re-skim spec/plan/tasks for path drift after implement

---

## Dependencies & Execution Order

- Phase 1 → Phase 2 → Phase 3 → Phase 4 → Phase 5 (T014 last) → Phase 6
- T003–T006 parallel OK after T002; T007–T008 after `arm64/` file exists; T009–T011 parallel OK; T012 before T014

### MVP

ARM64 deploy path + LAN GUI + MySQL not node-exposed + Pi doc with ingest URL form for `012`.

---

## Notes

- Prefer `#domain:docker-ufw` for compose/publish/UFW; `#domain:safe-script-production` for installer micro-edits; `#domain:iot-garden-php` for GUI/docs; `#domain:iot-core-ingest` for HTTP-only / no MySQL-on-LAN reminders.
- **Do not** use umbrella `#domain:coding` for implement turns.
- Ingest **endpoint code** may be implemented in `012`; this feature stops at host + docs readiness.
- One Alfred-sized task per Continue chat (`/speckit.implement` or paste one T0xx).
- Do not commit `.env`, WiFi passwords, or device keys.

### FR → task coverage (for analyze)

| FR | Tasks |
|----|-------|
| FR-001 | T003, T004, T014 |
| FR-002 | T005, T006 |
| FR-003 | T007, T008 |
| FR-004 | T009, T012, T014 |
| FR-005 | T009, T013 |
| FR-006 | T011 |
| FR-007 | T010 |
| FR-008 | T012, T013 |
| FR-009 | T012 doc note only (no SSD implement) |
