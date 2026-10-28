# Feature Specification: Plot & Summary APIs

**Feature Branch**: `005-plot-summary-apis`  
**Created**: 2026-07 (retrospective)  
**Status**: Delivered  
**Input**: Backfilled from Bokeh/Python plot pipeline, `public/api/plot.php`, `public/api/summary.php`, and deterministic dashboard seeding.

## User Scenarios & Testing

### User Story 1 - Interactive plant plots (Priority: P1)

A user filters by plant on the dashboard and sees a Bokeh-powered plot generated from stored readings.

**Why this priority**: Visualization is the core value of the monitoring UI.

**Independent Test**: Call plot API for a seeded plant; confirm valid JSON/HTML payload and UI render.

**Acceptance Scenarios**:

1. **Given** plant readings exist, **When** dashboard requests a plot, **Then** Python plot generation returns usable output parsed by `plot.php`.
2. **Given** a plant filter, **When** plot is requested, **Then** only that plant’s series is visualized.

### User Story 2 - Summary metrics cards (Priority: P1)

Dashboard summary cards show current moisture/temp/humidity (and later EC/pH) for the selected plant context.

**Independent Test**: `GET` summary API with plant filter returns expected metric keys.

### User Story 3 - Deterministic local validation data (Priority: P2)

Deploy seeding creates enough readings per plant so local UI checks are not flaky empty states.

**Independent Test**: After seed, every demo plant has temperature/humidity/moisture samples.

## Requirements

### Functional Requirements

- **FR-001**: `public/api/plot.php` MUST invoke Python plotting robustly (path/JSON handling, not fragile stdout assumptions).
- **FR-002**: `public/api/summary.php` MUST return plant-filterable summary metrics as JSON.
- **FR-003**: Python plot scripts MUST run in the project venv used by deploy.
- **FR-004**: Local/test seeds MUST provide deterministic multi-metric readings for dashboard checks.
- **FR-005**: Sensors’ last-reading bookkeeping SHOULD stay consistent with ingest where migrations apply.

## Success Criteria

- **SC-001**: Local deploy shows non-empty plots for seeded plants.
- **SC-002**: Summary endpoint drives dashboard cards without server errors.

## Assumptions

- Bokeh/Python remain the plot engine; PHP is the HTTP façade.
- HTTP ingest (vs direct MySQL from nodes) is the preferred long-term path.
