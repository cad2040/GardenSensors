# EC nutrient proxy

GardenSensors uses **soil electrical conductivity (EC)** as a trend-based proxy for plant nutrient availability—not isolated nitrate (NO₃).

## Concepts

- **Soil EC** measures total dissolved salts (Ca, Mg, Na, fertilizers, etc.).
- **Available nutrient EC** = `max(0, soil_EC − tap_water_baseline_EC)` (per user in Settings).
- Alerts use **thresholds** and **7-day rolling trend** (see `EcNutrientService`).

## Default plant zones (mS/cm, available EC)

| State | Condition |
|-------|-----------|
| Underfed | &lt; 0.8, or consistent downward trend |
| Balanced | 1.0 – 1.6 |
| Overfed | &gt; 2.0 (flush risk) |

Per-plant overrides: `ec_underfed`, `ec_balanced_min`, `ec_balanced_max`, `ec_overfed`.

## Calibration

1. Measure tap water EC with the probe (or a meter) and save as **baseline water EC** in Settings (default 0.3 mS/cm).
2. Compare trends over days/weeks, not single readings.
3. Do not treat EC as laboratory nitrate-N.

## Firmware

RS485 7-in-1 probes: EC from Modbus holding register `0x0002`, scaled as `raw × 0.01` mS/cm (verify on your datasheet).
