# Implementation Plan: 007-plant-catalog

**Branch**: `007-plant-catalog` | **Date**: retrospective | **Spec**: [spec.md](./spec.md)  
**Status**: Delivered

## Summary

Introduce a shared `plant_catalog` with CSV import, UI dropdowns on add/edit plant, opt-in seeding, and cleanup wipe support so thresholds come from presets without auto-seeding every deploy.

## Technical Context

**Language/Version**: PHP, MySQL  
**Primary Dependencies**: `PlantCatalog` model, import CLI, setup/cleanup hooks  
**Storage**: `database/migrations/003_create_plant_catalog.sql`, CSV under `database/catalog/`  
**Testing**: `PlantCatalogTest`, import unit/DB tests  
**Target Platform**: Bare-metal and Compose deploys  
**Constraints**: Opt-in seed only

## Project Structure

```text
database/migrations/003_create_plant_catalog.sql
database/catalog/
database/import_plant_catalog.php
src/Models/PlantCatalog.php
public/add_plant.php
public/edit_plant.php
public/get_plant_catalog.php
tests/Models/PlantCatalogTest.php
```

**Structure Decision**: Catalog is data + import tooling in `database/`; UI consumes via model/API.
