# Tasks: 003-auth-and-security

**Input**: `/specs/003-auth-and-security/`  
**Status**: Delivered — all tasks complete (retrospective)

## Phase 1: Configuration

- [x] T001 Add `.env.example` and wire app config to environment variables
- [x] T002 Remove hardcoded DB credentials from web config paths

## Phase 2: Authentication

- [x] T003 [US1] Implement login/logout and session handling
- [x] T004 [US1] Add user registration / seeded admin bootstrap
- [x] T005 [US1] Protect mutating plant/sensor management routes

## Phase 3: API hardening

- [x] T006 [US3] Implement rate limiting for API endpoints
- [x] T007 Verify unauthorized access is denied on protected pages

## Notes

Backfilled across “application improvements”, credential fixes, and `src/Services` auth/rate-limit code.
