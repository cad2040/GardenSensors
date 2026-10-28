# Feature Specification: Web Dashboard & Plant/Sensor CRUD

**Feature Branch**: `002-web-dashboard-crud`  
**Created**: 2024–2025 (retrospective)  
**Status**: Delivered  
**Input**: Backfilled from PHP web GUI, plant/sensor management pages, and dashboard tabs.

## User Scenarios & Testing

### User Story 1 - View garden status on a dashboard (Priority: P1)

A user opens the web app and sees plant/plot views with embedded visualizations and current garden data.

**Why this priority**: Primary operator interface for the monitoring system.

**Independent Test**: Load `public/index.php` / Homepage and confirm tabs and plant data render.

**Acceptance Scenarios**:

1. **Given** seeded plants and readings, **When** the dashboard loads, **Then** plot locations/plants are listed and visualizations can be shown.
2. **Given** multiple plants, **When** the user switches tabs/filters, **Then** the relevant plant context is shown.

### User Story 2 - Manage plants and sensors (Priority: P1)

Operators add, edit, delete, and link plants to sensors (including pin assignment) so automation and plots know what to watch.

**Independent Test**: Create a plant and sensor via UI; confirm DB rows and list pages update.

**Acceptance Scenarios**:

1. **Given** an authenticated session (when auth present), **When** user adds a plant/sensor, **Then** it appears on management pages.
2. **Given** existing plants and sensors, **When** user assigns a sensor/pin, **Then** the link is persisted for watering and metrics.

### User Story 3 - Export / inspect readings (Priority: P3)

Users can browse or export readings for offline review.

**Independent Test**: Open readings/export endpoints with sample data.

## Requirements

### Functional Requirements

- **FR-001**: System MUST provide a web dashboard summarizing garden/plant status.
- **FR-002**: Users MUST be able to CRUD plants and sensors from the web UI.
- **FR-003**: System MUST support assigning sensors (and GPIO pins where applicable) to plants.
- **FR-004**: Dashboard MUST be able to display per-plot visualizations (iframe/plot integration).
- **FR-005**: Readings MUST be listable/exportable from the web app.

### Key Entities

- **Plant**: Named plant with thresholds and metadata.
- **Sensor**: Device metadata and optional pin.
- **Plant–Sensor link**: Association used instead of deprecated `fact_plants` (migrated to `plant_sensors`).

## Success Criteria

- **SC-001**: Operator can manage garden inventory without raw SQL.
- **SC-002**: Dashboard shows living data for configured plants after ingest.

## Assumptions

- Apache + PHP serve `public/` as the web root.
- Early UI evolved into PSR-4 `src/` controllers/models without changing the user-facing job.
