# Feature Specification: Plant Catalog Presets

**Feature Branch**: `007-plant-catalog`  
**Created**: 2026-07 (retrospective)  
**Status**: Delivered  
**Input**: Backfilled from `plant_catalog` schema, CSV import, UI dropdowns, and opt-in seed/cleanup wipe.

## User Scenarios & Testing

### User Story 1 - Pick thresholds from a shared catalog (Priority: P1)

When adding or editing a plant, a user selects a catalog preset and gets sensible moisture/related thresholds without hand-entering every field.

**Why this priority**: Speeds setup and keeps recommended ranges consistent.

**Independent Test**: Open add/edit plant, choose a catalog entry, confirm fields populate; save plant.

**Acceptance Scenarios**:

1. **Given** catalog rows exist, **When** user selects a preset, **Then** plant form fields fill from catalog mapping.
2. **Given** no auto-seed flag, **When** deploy runs, **Then** catalog is not forcibly loaded every time.

### User Story 2 - Opt-in import and cleanup (Priority: P2)

Operators import catalog CSV via CLI/hooks and can wipe catalog data on cleanup without destroying unrelated garden data unexpectedly.

**Independent Test**: Run import with seed CSV; run cleanup wipe path; verify table state.

## Requirements

### Functional Requirements

- **FR-001**: System MUST store plant catalog presets in MySQL (`plant_catalog`).
- **FR-002**: Add/edit plant UI MUST offer catalog-driven defaults/dropdowns.
- **FR-003**: CSV import MUST be available (`database/import_plant_catalog.php` + hooks).
- **FR-004**: Catalog seeding MUST be opt-in (env/flag), not mandatory on every deploy.
- **FR-005**: Cleanup MUST be able to wipe catalog data when requested.
- **FR-006**: Unit/integration tests MUST cover mapping and import behavior.

### Key Entities

- **PlantCatalog entry**: Preset name and threshold defaults.
- **Plant**: May be created/updated using catalog values.

## Success Criteria

- **SC-001**: New plant can be created from a preset in under a minute.
- **SC-002**: Default deploys do not surprise-overwrite catalog unless opted in.

## Assumptions

- Catalog CSV examples live under `database/catalog/`.
- Threshold semantics align with later EC fields where migrations extend plants.
