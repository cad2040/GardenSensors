# Implementation Plan: 001-sensor-ingest-foundation

**Branch**: `001-sensor-ingest-foundation` | **Date**: retrospective | **Spec**: [spec.md](./spec.md)  
**Status**: Delivered

## Summary

Establish the first end-to-end path from ESP8266 soil sampling to MySQL persistence, plus Python connectivity used by plot/pump scripts.

## Technical Context

**Language/Version**: Arduino C++ (ESP8266), Python 3, MySQL 8  
**Primary Dependencies**: ESP8266WiFi / MySQL connectors (era-specific), Python DB driver  
**Storage**: MySQL (`SQLDeployScript.sql` → later `database/schema.sql`)  
**Testing**: Manual flash + SQL verification (automated suite came later)  
**Target Platform**: ESP8266 NodeMCU + Ubuntu/MySQL host  
**Project Type**: IoT firmware + database foundation  
**Constraints**: LAN reachability to DB in early PoC; secrets not committed  
**Scale/Scope**: Single garden / few sensors

## Constitution Check

- Ingest security later tightened to HTTP-only; this foundation predates that rule.
- No secrets in git: connection details moved to env over time.

## Project Structure

```text
arduino/sketch_SoilSensor.ino
SQLDeployScript.sql          # legacy deploy reference
database/schema.sql          # current canonical schema (evolved)
python/                      # DBConnect and early automation scripts
```

**Structure Decision**: Firmware lives under `arduino/`; schema under `database/` (legacy root SQL retained for reference).

## Implementation Notes (as shipped)

- Sensors dimension + readings FK added early.
- Analog remap: wet ~270 → 100%, dry ~722 → 0%.
- Sample delay tuned for field practicality vs battery/WiFi load.
