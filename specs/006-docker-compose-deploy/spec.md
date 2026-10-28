# Feature Specification: Docker Compose Deploy Path

**Feature Branch**: `006-docker-compose-deploy`  
**Created**: 2026-07 (retrospective)  
**Status**: Delivered  
**Input**: Backfilled from Docker Compose stack, `setup.sh`/`cleanup.sh` docker modes, and container seed parity.

## User Scenarios & Testing

### User Story 1 - Run the stack in Compose (Priority: P1)

A deployer brings up PHP, Python, and MySQL via Docker Compose with documented env examples and health-gated readiness.

**Why this priority**: Gives a reproducible alternative to bare-metal Apache installs.

**Independent Test**: `docker compose up` (via project scripts) reaches healthy services; dashboard responds.

**Acceptance Scenarios**:

1. **Given** `.env.docker.example` copied/configured, **When** Compose stack starts, **Then** web and DB health checks pass.
2. **Given** a running stack, **When** tests run in the docker mode path, **Then** they execute against containerized services.

### User Story 2 - Parity with bare-metal CRUD/API (Priority: P1)

Plant/sensor CRUD and plot/summary APIs work under Compose the same as local Apache deploy.

**Independent Test**: Exercise CRUD + plot API against Compose deployment.

### User Story 3 - Tear down cleanly (Priority: P2)

`cleanup.sh` docker mode removes containers/volumes as documented without stranding host state.

## Requirements

### Functional Requirements

- **FR-001**: Repository MUST provide `docker-compose.yml` and PHP/Python Dockerfiles.
- **FR-002**: `setup.sh` / `cleanup.sh` MUST support docker-oriented modes with health gating.
- **FR-003**: Container seed SQL MUST provide dashboard-viable demo data.
- **FR-004**: Docs under `docs/setup/docker.md` MUST describe the Compose path.
- **FR-005**: Plot/Python resolution MUST work inside the container layout.

## Success Criteria

- **SC-001**: Compose stack serves dashboard and passes health-gated tests.
- **SC-002**: Bare-metal and Compose paths both verify cleanly for core CRUD/API.

## Assumptions

- Docker Engine + Compose v2 available on deployer host.
- LAN-only binding preferences follow estate/constitution guidance where applied.
