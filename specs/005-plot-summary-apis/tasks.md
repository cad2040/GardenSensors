# Tasks: 005-plot-summary-apis

**Input**: `/specs/005-plot-summary-apis/`  
**Status**: Delivered — all tasks complete (retrospective)

## Phase 1: Plot API

- [x] T001 [US1] Implement/harden `public/api/plot.php` path and JSON handling
- [x] T002 [US1] Align Python plot scripts with API invocation
- [x] T003 [US1] Add/extend Python plot tests

## Phase 2: Summary API

- [x] T004 [US2] Implement `public/api/summary.php` with plant filtering
- [x] T005 [US2] Wire dashboard cards to summary metrics

## Phase 3: Seeding & validation

- [x] T006 [US3] Expand deploy seed so each plant has temp/humidity/moisture readings
- [x] T007 Add GUI page tests for dashboard plot presence
- [x] T008 Confirm `setup.sh local` validates plot path end-to-end

## Notes

Backfilled primarily from `f6900c3` and `067d7df` (stabilize dashboard plotting + deterministic metrics).
