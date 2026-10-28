# Tasks: 001-sensor-ingest-foundation

**Input**: `/specs/001-sensor-ingest-foundation/`  
**Status**: Delivered — all tasks complete (retrospective)

## Phase 1: Schema

- [x] T001 Create sensors dimension and readings tables with FK
- [x] T002 Document / check in SQL deploy script for MySQL

## Phase 2: Firmware

- [x] T003 [US1] Implement ESP8266 soil sample + MySQL insert sketch
- [x] T004 [US1] Add configurable delay between readings
- [x] T005 [US1] Remap analog conductivity to 0–100% moisture

## Phase 3: Python helpers

- [x] T006 [P] Add Python MySQL connect/query helpers for plots and pump jobs
- [x] T007 Verify end-to-end reading insert from a flashed node

## Notes

Backfilled from early master history (`Initial commit` through sensor/plant SQL and sketch updates).
