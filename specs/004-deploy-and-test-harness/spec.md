# Feature Specification: Deploy Scripts & Test Harness

**Feature Branch**: `004-deploy-and-test-harness`  
**Created**: 2025–2026 (retrospective)  
**Status**: Delivered  
**Input**: Backfilled from repo restructure, `setup.sh` / `cleanup.sh`, and PHPUnit + Python test gating.

## User Scenarios & Testing

### User Story 1 - One-command local deploy (Priority: P1)

A developer runs `sudo ./setup.sh local` (after `.env` setup) and gets Apache + MySQL app + dependencies ready for UI work.

**Why this priority**: Past agent sessions repeatedly reinvented local deploy; canonical scripts are the product.

**Independent Test**: On a clean Ubuntu-like host, run local setup and open the dashboard.

**Acceptance Scenarios**:

1. **Given** `.env` and `.env.test` from examples, **When** `setup.sh local` completes, **Then** the web UI is served and DB is usable.
2. **Given** a deployed local stack, **When** `./cleanup.sh` runs, **Then** local deploy artifacts are torn down predictably.

### User Story 2 - Tests gate deploy (Priority: P1)

Local/production setup runs PHP and Python tests and fails the deploy on failure.

**Independent Test**: Introduce a failing PHPUnit test and confirm setup aborts; fix and confirm green path.

### User Story 3 - Clear package layout (Priority: P2)

Contributors find domain code under `src/`, web entry under `public/`, schema under `database/`, docs under `docs/`.

**Independent Test**: Autoload `GardenSensors\` from `src/`; no parallel abandoned `GUI/` tree required.

## Requirements

### Functional Requirements

- **FR-001**: `setup.sh` MUST support `local`, `test`, and `production` modes.
- **FR-002**: `cleanup.sh` MUST reverse local/test deploy side effects safely.
- **FR-003**: Local/production setup MUST run the automated test suite and fail closed on failure.
- **FR-004**: Repository layout MUST keep web entry in `public/` and domain code in `src/` (PSR-4).
- **FR-005**: Test DB helpers and seed/cleanup SQL MUST support deterministic verification.

## Success Criteria

- **SC-001**: New contributor can follow README quick start without bespoke install scripts.
- **SC-002**: CI/`setup.sh` catches regressions in PHP models and Python plot helpers.

## Assumptions

- Root is required for Apache/system package steps on bare metal.
- WSL vs Windows clones should not fork alternate deploy trees.
