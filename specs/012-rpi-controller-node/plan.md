# Implementation Plan: 012-rpi-controller-node

**Branch**: `012-rpi-controller-node` | **Spec**: [spec.md](./spec.md)  
**Status**: Planned

# Architecture & Implementation How: Raspberry Pi Controller Node Integration

## 1. Installer Flags & Platform Detection
- **`--target-platform=linux/arm64`**: Extend `docker-install.sh` to accept this flag for RPi deployments; triggers ARM64-specific image pulls and configuration overrides.
- **Platform detection**: Detect host architecture (`uname -m`) and auto-select appropriate manifest lists; default to x86 unless `--target-platform` specified. Fail-closed on missing ARM64.
- **Compose override layout**: Create `arm64/` subdirectory under main Compose stack with platform-specific overrides (resource limits, volume mounts).

## 2. Memory & Resource Limits Strategy
- **Absolute memory allocation**: Use absolute memory limits in `arm64/` override — not relative percentages (`--memory=50%`) in root compose defaults for Desktop. Optimize for 2–8 GB RAM Pis.
- **OOM protection**: Implement cgroups-based OOM protection via Compose overrides; prioritize stateless services for read-only layers.
- **Read-only filesystems**: Stateless services get read-only root layers; tmpfs mounts for ephemeral data (sessions, caches).

## 3. Network & Firewall Architecture
- **LAN subnet publishing**: Services publish on LAN subnets (e.g., `192.168.x.x:port`) where needed for dashboard access; internal bindings (`127.0.0.1`) optional if external LAN reachability required.
- **UFW/DOCKER-USER rules**: Docker ports align with UFW rules (`DOCKER-USER` for LAN RETURN, DROP for WAN); use `hostPort:containerPort` syntax in Compose files.
- **Single interface routing**: Single Ethernet port (or Wi-Fi fallback); no multi-homing or VLAN tagging unless explicitly supported by RPi model.

## 4. Credential & Secret Policy
- **v1 policy**: Harden permissions on `.env` files (`chmod 600`), avoid committing secrets to version control; Docker secrets migration deferred to later phase.
- **Secret rotation**: Create procedure for secret rotation once Docker secrets infrastructure is in place (phase-2).
- **Audit & remediate**: Audit all service configurations for hardcoded keys before deployment; use encrypted volumes where feasible.

## 5. Storage Optimization Strategy
- **SD card wear mitigation**: Prefer read-only layers, tmpfs mounts for ephemeral data; external SSD (`/mnt/SDD1`) treated as optional/TBD in initial deployment.
- **Monitoring script**: Create `du_depth`-based monitoring for SD card wear; alert on >80% usage threshold (optional).

## 6. Telemetry & Health Monitoring (Optional/Phase-2)
- **HTTP-only ingest**: Optional telemetry endpoint with `device_key` authentication for RPi health metrics (CPU, memory, disk); not required in v1 ship.
- **Health checks**: Standard Compose health checks; integrate with central monitoring stack only if telemetry enabled.

## 7. Installer Script Modularization
- **RPi-specific isolation**: Isolate RPi logic in `arm64/` subdirectory or conditional block within `docker-install.sh`; avoid bloating main installer.
- **Documentation**: Document ARM64 deployment flags and platform detection in installer README.

## Out of Scope (for this feature)
- **VRAM/runtime concerns** (`#domain:alfred-vram-runtime`): Not applicable to RPi; belongs to GPU/LLM runtime stack.
- **C++ resource pools** (`#domain:cpp-resource-pools`): Not relevant for containerized deployments on RPi.
- **Backup stages as secrets management** (`#domain:alfred-backup-stages`): Secrets migration is a separate phase; backup procedures belong to `#domain:alfred-backup-stages` domain, not this feature.

---
*End of Architecture Document*

