# Implementation Plan: 002-web-dashboard-crud

**Branch**: `002-web-dashboard-crud` | **Date**: retrospective | **Spec**: [spec.md](./spec.md)  
**Status**: Delivered

## Summary

Ship a PHP web GUI for garden monitoring: dashboard tabs, plant/sensor CRUD, pin assignment, and plot embedding, later cleaned into `public/` + `src/` layout.

## Technical Context

**Language/Version**: PHP 7.4+/8.x  
**Primary Dependencies**: Apache, MySQL, session-based pages  
**Storage**: MySQL plants, sensors, readings, `plant_sensors`  
**Testing**: Later PHPUnit + Python GUI page checks  
**Target Platform**: Ubuntu + Apache document root  
**Project Type**: Server-rendered web application  
**Constraints**: Keep domain logic moving into `GardenSensors\` namespace over time

## Project Structure

```text
public/                 # web entry (index, plants, sensors, CRUD endpoints)
public/includes/        # shared PHP includes
src/Models/             # Plant, Sensor, Reading, …
src/Controllers/        # PlantController, DashboardController, …
```

**Structure Decision**: Web-facing scripts under `public/`; reusable domain under `src/`.

## Implementation Notes (as shipped)

- Homepage/dashboard fetches plot locations and embeds visualizations.
- CRUD pages: add/edit/delete plant & sensor; assignSensor/assignPin.
- `fact_plants` removed in favor of `plant_sensors` (migration `002_migrate_fact_plants_to_plant_sensors.sql`).
