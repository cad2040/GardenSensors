# Feature Specification: Sensor Ingest Foundation

**Feature Branch**: `001-sensor-ingest-foundation`  
**Created**: 2024 (retrospective)  
**Status**: Delivered  
**Input**: Backfilled from shipped ESP8266 sketch, MySQL schema, and early Python DB helpers.

## User Scenarios & Testing

### User Story 1 - Capture soil readings from a field node (Priority: P1)

A gardener flashes an ESP8266 NodeMCU with the soil-sensor sketch. The node wakes, samples moisture (and later multi-metric probe data), and stores readings in MySQL for later visualization.

**Why this priority**: Without reliable ingest there is no monitoring product.

**Independent Test**: Flash firmware, confirm rows appear in `sensor_readings` / readings tables for a known sensor id.

**Acceptance Scenarios**:

1. **Given** a configured NodeMCU and reachable MySQL host, **When** the sketch runs a sample cycle, **Then** a reading is persisted with sensor id, value, and timestamp.
2. **Given** analog moisture raw counts, **When** values are remapped, **Then** moisture is stored on a 0–100% scale using calibrated wet/dry endpoints.

### User Story 2 - Maintain a sensor dimension (Priority: P2)

Operators can register sensors in a dimension table so readings join to named devices.

**Independent Test**: Insert a sensor row and confirm readings FK to it.

**Acceptance Scenarios**:

1. **Given** schema deployed, **When** a sensor is inserted, **Then** subsequent readings reference that sensor id.

### Edge Cases

- WiFi or DB unreachable: sketch should not corrupt local state; retries/delays apply.
- Out-of-range analog values: clamp or map using calibrated endpoints.

## Requirements

### Functional Requirements

- **FR-001**: System MUST accept soil moisture (and related) readings from ESP8266 firmware into MySQL.
- **FR-002**: System MUST identify readings by sensor id with a sensors dimension table.
- **FR-003**: Firmware MUST support a configurable sample delay between readings.
- **FR-004**: Moisture MUST be expressible as a 0–100% scale from calibrated raw conductivity.
- **FR-005**: Python helpers MUST be able to connect and query MySQL for downstream jobs (plots/pump).

### Key Entities

- **Sensor**: Named device / GPIO assignment metadata.
- **Reading**: Timestamped measurement linked to a sensor.

## Success Criteria

- **SC-001**: A flashed NodeMCU produces durable reading rows without manual SQL inserts.
- **SC-002**: Schema supports joining sensors to readings for dashboard and automation scripts.

## Assumptions

- Single-estate LAN MySQL was acceptable for early PoC (later constrained to HTTP-only ingest).
- Hardware is NodeMCU / ESP8266 class with analog or RS485 probe paths evolving over time.

## Out of Scope (at delivery of this slice)

- Web dashboard UI, auth, Docker, alerts, enclosure CAD.
