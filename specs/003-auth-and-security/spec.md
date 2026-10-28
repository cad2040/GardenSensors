# Feature Specification: Auth & Security Hardening

**Feature Branch**: `003-auth-and-security`  
**Created**: 2025 (retrospective)  
**Status**: Delivered  
**Input**: Backfilled from user auth, env-based config, rate limiting, and related hardening.

## User Scenarios & Testing

### User Story 1 - Sign in to manage the garden (Priority: P1)

An operator registers/logs in and reaches protected management pages; anonymous users cannot mutate garden data.

**Why this priority**: Multi-user and exposed web UIs need access control.

**Independent Test**: Hit a protected page logged out (redirect/deny); log in as seed admin and succeed.

**Acceptance Scenarios**:

1. **Given** valid credentials, **When** user logs in, **Then** session is established and dashboard/management are accessible.
2. **Given** no session, **When** user opens a mutating endpoint, **Then** access is denied or redirected to login.

### User Story 2 - Configure secrets via environment (Priority: P1)

Deployers configure DB and app secrets through `.env` / `.env.example` rather than hardcoding credentials in PHP.

**Independent Test**: Copy `.env.example` → `.env`, deploy, confirm app connects without committed secrets.

### User Story 3 - Rate-limit abusive API traffic (Priority: P2)

API endpoints resist simple flood/abuse via rate limiting.

**Independent Test**: Exceed limit on a rate-limited endpoint and observe rejection.

## Requirements

### Functional Requirements

- **FR-001**: System MUST authenticate users (login/logout/session).
- **FR-002**: System MUST support user registration (or seeded admin bootstrap).
- **FR-003**: System MUST load configuration from environment files (not committed secrets).
- **FR-004**: System MUST rate-limit sensitive API endpoints.
- **FR-005**: Auth and session handling MUST fail closed on invalid/missing sessions.

### Key Entities

- **User**: Credentials and role/access metadata.
- **Session**: Server-side auth state for web requests.

## Success Criteria

- **SC-001**: Default local seed admin can log in after `setup.sh local`.
- **SC-002**: No production DB passwords committed in tracked source.

## Assumptions

- Default local credentials documented for redeploy (`admin` / `admin123`) and changed in real deployments.
- HTTPS termination may be provided by host/reverse proxy in production setups.
