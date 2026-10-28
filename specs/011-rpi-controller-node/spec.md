# Feature Specification: Raspberry Pi Controller Node Integration

**Feature Branch**: `011-rpi-controller-node`  
**Status**: Planned (docs incomplete — active Spec Kit feature; implement before pot-node bring-up)  
**Created**: 2026-10-28  
**Renumbered**: 2026-10-28 (was `002-rpi-controller-node`, then briefly `012`; swapped ahead of pot bring-up)  
**Blocks**: `012-pot-node-v15-bringup` (pot HTTP ingest + GUI need this LAN host)

## Ordering note

Ship this **before** outdoor pot-node bring-up (`012`). The Pi is the LAN stack the node POSTs into and the surface for the garden GUI. Mechanical print/hose-test in `012` can proceed in parallel offline, but **US3 first HTTP ingest / dashboard proof waits on this feature**.

## 1. Core Objectives
- **Secure LAN Integration**: The RPi node must join the existing estate network with strict LAN-only routing, adhering to UFW firewall policies and Docker port binding rules. Services should publish on LAN subnets (e.g., `192.168.x.x`) where needed for dashboard access; internal bindings (`127.0.0.1`) are optional if external LAN reachability is required.
- **ARM64 Compatibility**: All services deployed on the RPi must be ARM64-native or cross-platform compatible (e.g., via multi-arch images), avoiding x86-specific binaries.
- **Resource Efficiency**: Deployments must respect RPi memory ceilings (typically 2–8 GB RAM) and SD card I/O wear limits, prioritizing lightweight containers and read-only root filesystems where feasible. Use tiered/relative memory limits (e.g., `--memory=50%` or service-specific caps) rather than flat `--memory=4g` on all services to optimize for varying Pi configurations.
- **Thin Install Extensibility**: The `docker-install.sh` script must be extended to support RPi-specific environment flags (e.g., `--target-platform=linux/arm64`) without breaking existing x86 deployments.
- **Credential Security**: Harden permissions on `.env` files (`chmod 600`) and avoid committing secrets to version control in v1; Docker secrets migration can be a later acceptance criterion. Do not commit plaintext secrets first — use encrypted volumes or permission-restricted mounts where feasible.

## 2. Hardware & Resource Constraints
- **CPU Architecture**: RPi nodes operate on ARM64 (aarch64), requiring all container images to be explicitly built or pulled for this platform. Detect host architecture via `uname -m` and fail-closed if ARM64 missing; use `--target-platform=linux/arm64` overrides.
- **Memory Ceiling**: Assume a maximum of 8 GB RAM; enforce tiered memory limits (e.g., `--memory=50%` or service-specific caps) rather than flat `--memory=4g` on all services to optimize for varying Pi configurations.
- **Storage Medium**: SD cards have limited write endurance; prefer read-only layers, tmpfs mounts for ephemeral data, and external SSDs (e.g., `/mnt/SDD1`) as optional/TBD persistent storage — not a required path in the initial deployment.
- **Network Interface**: Single Ethernet port (or Wi-Fi fallback); no multi-homing or VLAN tagging unless explicitly supported by the RPi model.
- **Thermal Constraints**: Passive cooling assumed; avoid CPU-intensive workloads that risk thermal throttling.

## 3. Security & Network Boundaries
- **LAN-Only Routing**: Services should publish on LAN subnets (e.g., `192.168.x.x`) where needed for dashboard access; internal bindings (`127.0.0.1`) are optional if external LAN reachability is required. No `0.0.0.0` bindings unless explicitly whitelisted by UFW.
- **Firewall Policy**: Docker ports must align with UFW rules (`DOCKER-USER` for LAN, `DROP` for WAN). Use hostPort:containerPort syntax in Compose files.
- **Credential Handling**: Harden permissions on `.env` files (`chmod 600`) and avoid committing secrets to version control in v1; Docker secrets migration can be a later acceptance criterion. Audit all service configurations for hardcoded keys.
- **Image Hardening**: Use minimal base images (e.g., `alpine`, `scratch`) and scan all images for vulnerabilities before deployment.

## 4. Architecture Context
- **Environment Flags**: Extend `docker-install.sh` to accept a `--target-platform=linux/arm64` flag for RPi deployments. This flag should trigger ARM64-specific image pulls and configuration overrides.
- **Compose Overrides**: Create an `arm64/` subdirectory under the main Compose stack with platform-specific overrides (resource limits, volume mounts).
- **Script Modularity**: Ensure `docker-install.sh` remains modular; RPi-specific logic should be isolated in a dedicated script or conditional block to avoid bloating the main installer.
- **Telemetry Integration**: Telemetry ingest is optional/phase-2 unless desired in the first ship; implement HTTP-only telemetry with `device_key` authentication only if required for initial deployment.

## 5. Acceptance Criteria
- [ ] All container images are verified for ARM64 compatibility (`docker manifest inspect <image>`).
- [ ] Tiered memory limits are enforced on all services (e.g., `--memory=50%` or service-specific caps) rather than flat `--memory=4g`.
- [ ] No plaintext secrets committed to version control in v1; `.env` permissions hardened (`chmod 600`), Docker secrets migration deferred to later AC.
- [ ] UFW rules are updated to allow only LAN traffic for Docker ports; services publish on LAN subnets where needed for dashboard access.
- [ ] SD card usage is minimized via read-only layers and tmpfs mounts; external SSD (`/mnt/SDD1`) treated as optional/TBD, not required in initial deployment.
- [ ] The `docker-install.sh` script successfully deploys a test stack on an RPi without errors.
- [ ] Health checks pass for all services; telemetry ingest is optional/phase-2 unless explicitly enabled.
- [ ] No x86-specific binaries or dependencies are present in the deployment.

---
*End of Specification Document*