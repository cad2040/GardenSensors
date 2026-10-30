# Feature Specification: Raspberry Pi Controller Node (Garden Host)

**Feature Branch**: `011-rpi-controller-node`  
**Created**: 2026-10-28  
**Status**: Planned  
**Renumbered**: 2026-10-28 (was `002`, briefly `012`; ahead of pot bring-up)  
**Blocks**: `012-pot-node-v15-bringup` (pot HTTP ingest + GUI need this LAN host)  
**Input**: Deploy the GardenSensors Compose stack on an ARM64 Raspberry Pi as the LAN controller — dashboard GUI for operators and the future pot-node HTTP ingest target.

## Ordering note

Ship this **before** outdoor pot-node bring-up (`012`). Print/hose-test/Modbus in `012` can run offline; **US3 first HTTP ingest / dashboard proof waits on this host**.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Deploy garden stack on Pi ARM64 (Priority: P1)

An operator on a Raspberry Pi (ARM64) runs the thin Docker installer / Compose path and brings up `web` + `db` without x86-only images or flat Desktop memory assumptions.

**Why this priority**: Without a Pi-native stack there is no LAN host for GUI or pot ingest.

**Independent Test**: On an ARM64 Pi (or ARM64 VM), deploy via documented flags; `web` and `db` healthchecks pass; no deploy error from missing ARM64 manifests.

**Acceptance Scenarios**:

1. **Given** ARM64 host (`uname -m` is `aarch64`), **When** installer/compose is invoked with `--target-platform=linux/arm64` (or auto-detect), **Then** images pull/build for `linux/arm64` and services start.
2. **Given** a non-ARM64 host without override, **When** RPi-only path is forced incorrectly, **Then** platform detection fail-closes with a clear error (or docs state Desktop x86 remains the default path).

### User Story 2 - Use the garden GUI on the LAN (Priority: P1)

An operator opens the dashboard from a LAN browser (not only `localhost` on the Pi) and logs in with local credentials from `.env` / first-run answers (no secrets committed).

**Why this priority**: The Pi is the GUI surface for plant/sensor ops and for verifying pot readings later.

**Independent Test**: From another LAN client, open `http://<pi-lan-ip>:<http-port>/`; login page loads; health endpoint/login succeeds.

**Acceptance Scenarios**:

1. **Given** stack healthy on Pi, **When** operator browses to the published web port on the Pi’s LAN address, **Then** GUI login page is reachable.
2. **Given** `.env` / first-run secrets on disk, **When** files are inspected, **Then** permissions are `600` (or equivalent owner-only) and secrets are not in git.

### User Story 3 - LAN-safe publish posture (Priority: P1)

MySQL is not reachable from the LAN as a node ingest path; Docker/UFW posture allows LAN to web only as documented; pot nodes will use HTTP ingest (added/used in `012`), never ESP→MySQL on `0.0.0.0`.

**Why this priority**: Constitution — HTTP-only ingest; MySQL not on `0.0.0.0` for nodes.

**Independent Test**: From LAN client, web port reachable; MySQL host publish is localhost-only or not published; docs state ingest URL form for `012`.

**Acceptance Scenarios**:

1. **Given** running Pi stack, **When** probing MySQL from another LAN host on any published DB port, **Then** connection is refused / not advertised on `0.0.0.0` for node use.
2. **Given** docs for this feature, **When** operator reads the Pi deploy note, **Then** LAN base URL and future ingest path (`/api/ingest.php` behind DocumentRoot `public/`) are documented for `012`.

### User Story 4 - Pi resource envelope (Priority: P2)

Compose overrides for ARM64 apply **absolute** memory caps for a **4 GB Pi class** SoT (`web` `768m`, `db` `1536m` — not Desktop `--memory=50%` folklore), with tmpfs/read-only where practical for SD wear. 2 GB boards may need further tuning or disabling the `python` tools profile; 8 GB boards may raise caps only with documented rationale. External SSD and SD wear monitors remain out of v1 (FR-009).

**Independent Test**: `arm64/` override (or equivalent) sets those absolute limits; root compose is not the sole place for Pi caps.

## Requirements

### Functional Requirements

- **FR-001**: System MUST deploy GardenSensors `web` + `db` on ARM64 via Compose using multi-arch images or explicit `linux/arm64` platform.
- **FR-002**: `scripts/docker-install.sh` (and/or `setup.sh docker`) MUST accept or document `--target-platform=linux/arm64` without breaking the existing x86/Desktop path. This flag **selects** the `arm64/` compose chain (FR-003); it does not replace the override file.
- **FR-003**: ARM64-specific resource limits MUST live under `arm64/docker-compose.yml` using **absolute** `mem_limit` values for a **4 GB Pi class**: `web` = `768m`, `db` = `1536m` — not relative `--memory=50%` as the Pi SoT.
- **FR-004**: Web MUST be reachable on the LAN for GUI use; publish posture MUST be documented (host port + Pi LAN IP).
- **FR-005**: MySQL MUST NOT be published for pot-node access on `0.0.0.0`; use the Compose internal network and, if a host publish exists, bind `127.0.0.1` only. UFW MUST NOT open MySQL to the LAN.
- **FR-006**: `.env` / first-run answer files MUST be created with owner-only permissions (`chmod 600`); no plaintext secrets committed. v1 does **not** require a Docker Secrets migration path.
- **FR-007**: UFW/`DOCKER-USER` guidance MUST allow LAN→**web HTTP port**, deny WAN, and MUST NOT allow LAN→MySQL (`#domain:docker-ufw`).
- **FR-008**: Docs MUST record the Pi LAN base URL and the intended HTTP ingest URL for `012`: DocumentRoot is `public/`, so the URL path is `/api/ingest.php` → `http://<pi-lan>:<port>/api/ingest.php`, even if `public/api/ingest.php` is implemented in `012`. No extra security-header mandate for that future path in v1.
- **FR-009**: External SSD paths (e.g. `/mnt/SDD1`) and SD wear monitors are OPTIONAL/TBD — not required for v1 acceptance (no implement task).

### Non-Functional / Constraints

- Passive cooling / avoid unnecessary CPU-heavy jobs on the Pi.
- Telemetry/`device_key` RPi health ingest is **out of scope** for v1 (phase-2).
- Alfred VRAM domains do not apply on the Pi.

### Key Entities

- **Pi host**: ARM64 machine running Docker Engine + Compose.
- **Garden stack**: `web` (PHP/Apache, DocumentRoot `public/`), `db` (MySQL 8).
- **Operator client**: LAN browser for GUI.
- **Future pot node (`012`)**: HTTP client to ingest URL on this host.

## Success Criteria

- **SC-001**: On ARM64 Pi, documented deploy brings `web` + `db` to healthy within one operator session.
- **SC-002**: GUI login page reachable from a second LAN device via `http://<pi-lan-ip>:<port>/`.
- **SC-003**: MySQL is not usable from LAN as a node write path (refused / not published on `0.0.0.0`).
- **SC-004**: `arm64/docker-compose.yml` sets `web` `mem_limit: 768m` and `db` `mem_limit: 1536m` (or documented upward revision); deploy docs mention them.
- **SC-005**: `.env` / first-run secrets are mode `600` and absent from git status as untracked secrets only.
- **SC-006**: Pi deploy doc states LAN base URL + future `/api/ingest.php` ingest form for `012`.

## Assumptions

- Docker Engine + Compose v2 on the Pi.
- Operator has LAN IPv4 on the Pi (Ethernet preferred).
- Existing `docker-compose.yml` / `scripts/docker-install.sh` / `docs/setup/docker.md` are the baseline to extend — not a greenfield stack.
- HTTP ingest **implementation** may land in `012`; this feature MUST leave the host and docs ready.

## Out of Scope

- Pot enclosure print, firmware MySQL→HTTP migration (`012`).
- Docker Swarm/K8s; VLAN/multi-homing.
- Docker Secrets migration (phase-2); Alfred GPU/LiteLLM on Pi.
- Mandatory `/mnt/SDD1` or Alfred backup-stage wiring.
