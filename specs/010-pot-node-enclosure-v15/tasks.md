# Tasks: 010-pot-node-enclosure-v15

**Input**: `/specs/010-pot-node-enclosure-v15/`  
**Status**: Delivered — all tasks complete (retrospective)

## Phase 1: BOM & CAD baseline

- [x] T001 [US1] Author locked outdoor PoC `HARDWARE_BOM.md`
- [x] T002 [US2] Create OpenSCAD pot-node enclosure (flange seal, gasket path)
- [x] T003 Link BOM/enclosure from setup README and root README

## Phase 2: V1.5 fit & USB-C

- [x] T004 [US2] Enlarge cavity; side-by-side MCU/probe/power layout
- [x] T005 [US2] Fix TP4056 short-edge USB-C shelf and mounts
- [x] T006 Geometry audit: BOM parts fit, install order preserves wiring access

## Phase 3: Print handoff & assembly

- [x] T007 [US2] Export and commit STLs (body, lid, gasket, USB cap)
- [x] T008 [US2] Add `export_stls.sh` for regeneration
- [x] T009 [US3] Write `POT_NODE_ASSEMBLY.md` (wiring order, solder vs Dupont, pre-close)
- [x] T010 Update enclosure README for V1.5 print/assembly pointers

## Notes

Backfilled from `702a2dc`, enclosure portions of `a5df8bd`, and `ccd38d7`.  
Physical print → flash → first HTTP ingest continues in `012-pot-node-v15-bringup`.
