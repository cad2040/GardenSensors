# Tasks: 006-docker-compose-deploy

**Input**: `/specs/006-docker-compose-deploy/`  
**Status**: Delivered — all tasks complete (retrospective)

## Phase 1: Compose stack

- [x] T001 [US1] Add `docker-compose.yml` for PHP, Python, MySQL
- [x] T002 [US1] Add Dockerfiles and `.env.docker.example`
- [x] T003 [US1] Add container MySQL seed for dashboard parity

## Phase 2: Script & API parity

- [x] T004 [US1] Extend `setup.sh` / `cleanup.sh` with docker modes + health gates
- [x] T005 [US2] Harden plot/Python resolution for container paths
- [x] T006 [US2] Fix plant/sensor CRUD endpoints for Compose + bare-metal

## Phase 3: Docs & verification

- [x] T007 Document Compose path in `docs/setup/docker.md` and README
- [x] T008 Run health-gated tests against Compose stack

## Notes

Backfilled from commit `84cde88`.
