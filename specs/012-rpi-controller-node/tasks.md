# Tasks: 012-rpi-controller-node

**Input**: Design documents from `/specs/012-rpi-controller-node/`  
**Status**: Planned

# Raspberry Pi Controller Node Implementation

## Phase 1: ARM64 Image Verification & Platform Detection
**Goal**: Ensure all services are ARM64-compatible before deployment.

### Tasks
1. [ ] (#domain:safe-script-production) Verify existing images have ARM64 manifests via `docker manifest inspect`; create pull script with `--platform linux/arm64` flags for missing services in `scripts/docker-install.sh`
2. [ ] (#domain:coding) Audit Docker Compose files for x86-specific dependencies; replace with multi-arch or ARM64-native alternatives in `docker-compose.yml`

## Phase 2: Resource Limits & Memory Management
**Goal**: Enforce absolute memory limits in `arm64/` override — not relative percentages in root compose defaults.
### Tasks
3. [ ] (#domain:coding) Update `docker-compose.override.yml` with absolute memory limits (e.g., `--memory=2g`, `--memory=1g`) for RPi services instead of `--memory=50%` in root compose
4. [ ] (#domain:safe-script-production) Add read-only root filesystems and tmpfs mounts for ephemeral data in `docker-compose.yml`
## Phase 3: Credential Security Implementation
**Goal**: Harden secrets and avoid committing plaintext credentials in v1.

### Tasks
5. [ ] (#domain:safe-script-production) Harden `.env` permissions (`chmod 600`) after creation in `scripts/docker-install.sh`; document secret rotation procedure for phase-2 Docker secrets migration
6. [ ] (#domain:coding) Audit all service configs for hardcoded keys; remediate before deployment in `docker-compose.yml`

## Phase 4: Network & Firewall Configuration
**Goal**: Configure UFW rules and LAN-only publishing per estate policy.

### Tasks
7. [ ] (#domain:docker-ufw) Generate UFW rules for Docker ports (`DOCKER-USER` for LAN, `DROP` for WAN); ensure no `0.0.0.0` MySQL bindings
8. [ ] (#domain:coding) Update `docker-compose.yml` to publish services on LAN subnets (e.g., `192.168.x.x`) where dashboard access is needed; use `HOST:CONTAINER` port syntax
## Phase 5: Storage Optimization & Health Checks
**Goal**: Minimize SD card wear and validate deployment.

### Tasks
9. [ ] (#domain:safe-script-production) Mark `/mnt/SDD1` and SD wear monitoring as optional/TBD; not required for initial deployment
10. [ ] (#domain:coding) Run health checks for all services; verify no x86-specific binaries present in `docker-compose.yml`
---
*End of Tasks Document*

