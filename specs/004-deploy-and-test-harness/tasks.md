# Tasks: 004-deploy-and-test-harness

**Input**: `/specs/004-deploy-and-test-harness/`  
**Status**: Delivered — all tasks complete (retrospective)

## Phase 1: Repository structure

- [x] T001 Restructure into `public/`, `src/`, `database/`, `tests/`, `docs/`
- [x] T002 Remove redundant parallel deploy/GUI trees
- [x] T003 Configure PSR-4 `GardenSensors\` → `src/`

## Phase 2: Setup & cleanup

- [x] T004 [US1] Implement `setup.sh` modes: local / test / production
- [x] T005 [US1] Implement `cleanup.sh` for teardown
- [x] T006 Wire DB schema + seed/cleanup SQL helpers

## Phase 3: Test harness

- [x] T007 [US2] Add PHPUnit suite for models/services
- [x] T008 [US2] Add Python unit tests for plot/helpers
- [x] T009 [US2] Gate `setup.sh local`/`production` on test success
- [x] T010 Document testing and deployment under `docs/development/`

## Notes

Backfilled from structure cleanup commits through stabilized local deploy (`9e2593c` … `f6900c3` and follow-ups).
