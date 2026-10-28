# Feature Specification: Pot-Node BOM & Enclosure V1.5

**Feature Branch**: `010-pot-node-enclosure-v15`  
**Created**: 2026-07–2026-08 (retrospective)  
**Status**: Delivered  
**Input**: Backfilled from locked hardware BOM, OpenSCAD enclosure, V1.5 fit fixes, committed STLs, and assembly guide.

## User Scenarios & Testing

### User Story 1 - Know what to buy (Priority: P1)

A builder follows `docs/setup/HARDWARE_BOM.md` to purchase the locked outdoor pot-node stack (NodeMCU, RS485 probe path, power, fasteners).

**Why this priority**: Hardware ambiguity blocks any field deployment.

**Independent Test**: Review BOM for complete buy-list with footprints/links; no TBD on locked PoC parts.

**Acceptance Scenarios**:

1. **Given** the BOM doc, **When** a builder sources parts, **Then** each locked PoC component is specified (including USB-C TP4056 short-edge variant).

### User Story 2 - Print a weatherproof case (Priority: P1)

A friend prints committed STLs (PETG body/lid, TPU gasket/USB cap) produced from the OpenSCAD enclosure without needing CAD edits.

**Independent Test**: Open STLs in slicer; confirm printable meshes; optional regenerate via `export_stls.sh` after `.scad` changes.

**Acceptance Scenarios**:

1. **Given** V1.5 STLs in `hardware/enclosure/`, **When** printed in specified materials, **Then** BOM parts fit side-by-side without cavity collisions (geometry audit passed at commit time).
2. **Given** gasket + USB cap, **When** assembled, **Then** design intent supports hose-test (physical test tracked in `011`).

### User Story 3 - Assemble on the bench (Priority: P1)

Builder follows `POT_NODE_ASSEMBLY.md` for wiring order, solder vs Dupont choices, and pre-close checklist.

**Independent Test**: Walk checklist against BOM + enclosure README; no missing steps for V1.5 layout.

## Requirements

### Functional Requirements

- **FR-001**: Repository MUST document locked V1.0 PoC BOM with vendor/footprint notes.
- **FR-002**: Enclosure MUST be defined in OpenSCAD with flange/gasket seal path (screws outside gasket).
- **FR-003**: V1.5 MUST enlarge cavity and fix USB-C / mount layout for installable BOM fit.
- **FR-004**: Printable STLs MUST be committed for body, lid, gasket, USB cap.
- **FR-005**: Assembly guide MUST cover bench vs in-box steps and flash-before-final-torque guidance.
- **FR-006**: Enclosure README MUST explain export/regeneration via `export_stls.sh`.

## Success Criteria

- **SC-001**: Geometry audit: all BOM parts fit with documented install order.
- **SC-002**: External builder can print from STLs without opening OpenSCAD.
- **SC-003**: Assembly doc is linked from BOM and setup README.

## Assumptions

- Physical print/hose-test/first ingest are a separate planned feature (`011-pot-node-v15-bringup`).
- Materials: dark-green PETG + TPU gasket/cap as recommended in session notes.

## Out of Scope

- Auto-watering hardware/firmware automation (Priority B).
- RPi controller node (`012`).
