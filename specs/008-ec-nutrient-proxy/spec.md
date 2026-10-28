# Feature Specification: EC Nutrient Proxy

**Feature Branch**: `008-ec-nutrient-proxy`  
**Created**: 2026-07 (retrospective)  
**Status**: Delivered  
**Input**: Backfilled from EC-based nutrient proxy, firmware Modbus EC, `user_settings`, and stage-1 dashboard support.

## User Scenarios & Testing

### User Story 1 - See nutrient status from soil EC (Priority: P1)

A gardener views underfed / balanced / overfed status derived from available soil EC (soil EC minus tap baseline) plus trend—not fake nitrate ppm.

**Why this priority**: Matches real RS485 probe capabilities and avoids misleading NPK claims.

**Independent Test**: Seed EC readings + baseline; call summary/notification path; confirm zone classification.

**Acceptance Scenarios**:

1. **Given** baseline water EC and soil EC readings, **When** available EC is computed, **Then** status maps to underfed/balanced/overfed zones.
2. **Given** a downward multi-day trend, **When** evaluation runs, **Then** underfed can trigger even near borderline absolute values.

### User Story 2 - Calibrate tap baseline in settings (Priority: P1)

User sets **baseline water EC** in Settings after measuring tap water with the probe.

**Independent Test**: Save baseline via settings UI; confirm persistence in `user_settings` and use in `EcNutrientService`.

### User Story 3 - Firmware reports EC from RS485 probe (Priority: P1)

NodeMCU Modbus path reads EC holding register and includes it in ingest payload/metrics.

**Independent Test**: With probe (or mocked register values), firmware produces EC in expected scale (raw × 0.01 mS/cm).

## Requirements

### Functional Requirements

- **FR-001**: System MUST compute available nutrient EC as `max(0, soil_EC − baseline_water_EC)`.
- **FR-002**: System MUST classify nutrient state using thresholds and trend (`EcNutrientService`).
- **FR-003**: Plants MAY override EC zone thresholds (`ec_underfed`, `ec_balanced_min/max`, `ec_overfed`).
- **FR-004**: User settings MUST store baseline water EC and related prefs.
- **FR-005**: Firmware MUST read Modbus EC register `0x0002` (scaled) for supported probes.
- **FR-006**: Dashboard/summary MUST surface stage-1 EC nutrient support.
- **FR-007**: Docs MUST explain proxy semantics (`docs/development/ec-nutrient-proxy.md`).

### Key Entities

- **User settings**: Baseline water EC and notification prefs precursors.
- **Plant EC thresholds**: Per-plant zone overrides.
- **EC reading**: Soil conductivity in mS/cm.

## Success Criteria

- **SC-001**: Operators can calibrate baseline and see coherent nutrient status on dashboard.
- **SC-002**: No UI copy claims laboratory nitrate-N from this proxy.

## Assumptions

- CWT/JXCT-class RS485 4-in-1/7-in-1 probes; datasheet scale verified by operator.
- Email delivery of alerts is a follow-on (see `009`); classification logic lands here.
