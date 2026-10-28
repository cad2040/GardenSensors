# Implementation Plan: 008-ec-nutrient-proxy

**Branch**: `008-ec-nutrient-proxy` | **Date**: retrospective | **Spec**: [spec.md](./spec.md)  
**Status**: Delivered

## Summary

Replace nitrogen-oriented nutrient UX with an EC trend proxy: migrations for EC fields and `user_settings`, `EcNutrientService`, firmware Modbus EC, and stage-1 dashboard/summary support.

## Technical Context

**Language/Version**: PHP, Arduino/ESP8266, MySQL  
**Primary Dependencies**: `EcNutrientService`, notification hooks, RS485 Modbus in `sketch_SoilSensor.ino`  
**Storage**: Migrations `003_add_ph_nitrogen_support.sql`, `004_ec_nutrient_proxy.sql`, `005_user_settings.sql`  
**Testing**: `EcNutrientServiceTest`, model/seed tests, CI workflow updates  
**Target Platform**: Same garden stack + outdoor probe nodes  
**Constraints**: Document proxy limits; do not present EC as lab nitrate

## Project Structure

```text
src/Services/EcNutrientService.php
database/migrations/004_ec_nutrient_proxy.sql
database/migrations/005_user_settings.sql
arduino/sketch_SoilSensor.ino
public/api/summary.php
public/manage_settings.php / settings UI
docs/development/ec-nutrient-proxy.md
```

**Structure Decision**: Domain logic in `EcNutrientService`; firmware owns register read/scale; docs explain calibration.
