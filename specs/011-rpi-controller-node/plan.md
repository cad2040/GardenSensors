# Implementation Plan: 011-rpi-controller-node

**Branch**: `011-rpi-controller-node` | **Date**: 2026-10-30 | **Spec**: [spec.md](./spec.md)  
**Status**: Planned  
**Priority**: Ahead of `012-pot-node-v15-bringup` (this host is the ingest + GUI target)  
**Input**: Feature specification from `/specs/011-rpi-controller-node/spec.md`

## Summary

Extend the existing GardenSensors Docker path so an **ARM64 Raspberry Pi** can run `web` + `db` as the LAN garden controller: GUI for operators, MySQL kept off the LAN node path, and docs that name the future pot HTTP ingest URL for `012`. Isolate Pi resource limits in an **`arm64/` compose override** with **absolute** memory caps.

## Technical Context

**Language/Version**: Bash (`scripts/docker-install.sh`, `setup.sh`), Docker Compose v2, PHP/MySQL stack as today  
**Primary Dependencies**: `docker-compose.yml`, `docker/Dockerfile.php`, `docker/Dockerfile.python`, `scripts/docker-install.sh`, `docs/setup/docker.md`  
**Storage**: MySQL named volume; SD-friendly read-only/tmpfs where cheap; `/mnt/SDD1` optional TBD  
**Testing**: Compose healthchecks; LAN browser to login; `docker manifest inspect` / platform pull checks; optional `setup.sh docker` on Pi  
**Target Platform**: Raspberry Pi OS (or similar) **ARM64** + Docker Engine  
**Constraints**: Constitution — no secrets in git; MySQL not on `0.0.0.0` for nodes; LAN GUI; no Alfred VRAM concerns on Pi  
**Scale/Scope**: Single Pi controller for first pot-node generation

## Constitution Check

| Principle | Plan alignment |
|-----------|----------------|
| HTTP-only ingest; MySQL not on `0.0.0.0` for nodes | FR-005 / US3; DB internal or localhost publish only |
| No secrets in git | FR-006; chmod 600 on `.env` / first-run answers |
| LAN default | FR-004 / FR-007; UFW DOCKER-USER |
| Alfred-sized tasks | tasks.md phases; one coder task per chat |
| Docs / BOM alignment | FR-008 Pi deploy note + ingest URL for `012` |

## Feature Requirement Alignment

| FR | Task IDs | Notes |
|----|----------|-------|
| FR-001 ARM64 stack | T003, T004, T014 | manifests + `arm64/` platform + human Pi verify |
| FR-002 installer `--target-platform` | T005, T006 | **selects** `arm64/` compose chain; does not replace it |
| FR-003 absolute memory in `arm64/` | T007, T008 | **SoT values (4 GB Pi class):** `db` `mem_limit: 1536m`, `web` `mem_limit: 768m` |
| FR-004 LAN GUI | T009, T012, T014 | publish web + doc LAN URL + verify |
| FR-005 MySQL not LAN node path | T009, T013 | docker-internal / `127.0.0.1` only; HTTP-only reminder |
| FR-006 chmod 600 secrets | T011 | `.env` + `docker/first-run/answers.env`; no plaintext migration ceremony in v1 |
| FR-007 UFW / DOCKER-USER | T010 | LAN→**web HTTP port** allow; WAN drop; MySQL not in LAN allowlist |
| FR-008 docs + ingest URL form | T012, T013 | DocumentRoot `public/` ⇒ URL `/api/ingest.php` for `012` |
| FR-009 optional SSD | T012 (doc note only) | Documented as out of v1; no SSD mount / wear-monitor implement |

**Installer vs override:** `--target-platform=linux/arm64` (FR-002) tells the installer which compose files to use; `arm64/docker-compose.yml` (FR-001/003) holds platform + mem + publish. Both are required; neither supersedes the other.

## Project Structure

### Documentation (this feature)

```text
specs/011-rpi-controller-node/
├── plan.md
├── spec.md
└── tasks.md
```

### Source Code / artifacts (repository)

```text
docker-compose.yml                 # baseline web + db (+ python profile)
scripts/docker-install.sh          # extend: --target-platform=linux/arm64, chmod 600
setup.sh                           # docker mode may gain Pi notes / platform passthrough
arm64/
└── docker-compose.yml             # NEW: platform + absolute mem + SD-friendly mounts
docs/setup/docker.md               # extend Desktop path
docs/setup/rpi-controller.md       # NEW: Pi deploy, LAN URL, ingest URL for 012
docs/api/ingest-contract.md        # reference only (ingest impl may be 012)
```

**Structure Decision**: Keep root `docker-compose.yml` as Desktop/x86-friendly baseline. Add **`arm64/`** override compose (or `compose --file` chain) for Pi — absolute memory, `platform: linux/arm64` where needed, publish posture. Do not fork a second application tree.

## Implementation Approach

### Phase A — ARM64 baseline

1. Inventory images (`mysql:8.0`, PHP build, python tools) for ARM64 manifests.
2. Add `arm64/docker-compose.yml` (or documented override) with `platform:` / build args as required.
3. Extend `scripts/docker-install.sh` with `--target-platform=linux/arm64` and `uname -m` detect; fail-closed messaging when ARM64 required but missing.

### Phase B — Resource + publish posture

1. In **`arm64/` only**, set absolute caps for **4 GB Pi class** SoT: `web` `mem_limit: 768m`, `db` `mem_limit: 1536m` (2 GB may need further tuning; raise only with documented rationale for 8 GB boards).
2. Prefer tmpfs for ephemeral caches where Compose already allows; avoid rewriting app code. SSD paths / SD wear monitors stay out of v1 (FR-009).
3. Web: publish host HTTP port for LAN GUI. DB: docker-internal only, or host bind `127.0.0.1:3307` — never `0.0.0.0` for node use (T009 enforces in `arm64/` YAML).
4. Document UFW/`DOCKER-USER`: LAN→web port RETURN/ALLOW; do **not** allow LAN→MySQL; WAN DROP (T010).

### Phase C — Secrets + docs + verify

1. Ensure installer sets `chmod 600` on `.env` and `docker/first-run/answers.env`.
2. Write `docs/setup/rpi-controller.md`: prerequisites, install command, LAN URL, **ingest URL for 012** (`http://<pi-lan>:<port>/api/ingest.php`), link from `docs/setup/docker.md`.
3. Human gate: deploy on real Pi (or ARM64 runner); SC-001–SC-006 checklist.

## Risks / Rollback

| Risk | Mitigation |
|------|------------|
| Image lacks ARM64 | manifest inspect early; pin multi-arch tags; document blockers |
| OOM on 2 GB Pi | absolute caps + optional disable `python` tools profile |
| Accidental MySQL LAN expose | override removes wide DB ports; verify with LAN probe |
| Installer Desktop regression | flag-gated ARM64 path; default remains current behavior |
| Ingest.php missing until 012 | docs still publish URL form; 011 accepts GUI-only proof |

## Out of Scope

- Pot firmware / enclosure (`012`)
- Docker Secrets migration; RPi health telemetry with `device_key`
- `#domain:alfred-vram-runtime`, `#domain:alfred-backup-stages` as this feature’s SoT
- Mandatory `/mnt/SDD1`

## Complexity Tracking

| Violation / stretch | Why needed | Simpler alternative rejected |
|---------------------|------------|------------------------------|
| Separate `arm64/` compose | Keeps Desktop compose stable | Editing only root compose with 50% mem breaks Pi and Desktop differently |
