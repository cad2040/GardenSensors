# Plant catalog import

Optional reference data for the **Plant type** dropdown on Add/Edit Plant.
Deploy does **not** seed this table unless you opt in.

## Enable on deploy

```bash
# Bare-metal (local / test / production) — imports plant_catalog.seed.csv by default
SEED_PLANT_CATALOG=1 sudo -E ./setup.sh local

# Custom file
PLANT_CATALOG_FILE=database/catalog/my_plants.csv SEED_PLANT_CATALOG=1 sudo -E ./setup.sh local

# Or import anytime after schema exists
php database/import_plant_catalog.php --file=database/catalog/plant_catalog.seed.csv

# Docker
SEED_PLANT_CATALOG=1 sudo -E ./setup.sh docker
```

When `SEED_PLANT_CATALOG=1` and `PLANT_CATALOG_FILE` is unset, setup prefers
`plant_catalog.csv` if present, otherwise `plant_catalog.seed.csv`.

Cleanup (`cleanup.sh` / `truncate_all_data.sql`) removes `plant_catalog` rows along
with other seed tables. Re-import only happens when the seed flag is set again.

## CSV format

Copy `plant_catalog.csv.example` to `plant_catalog.csv` (gitignored if you prefer)
and fill rows. Header columns:

| Column | Notes |
|--------|--------|
| `external_pid` | Upstream id (Open Plantbook pid, etc.); unique with `source` |
| `common_name` | Required |
| `scientific_name` | Maps to plant `species` |
| `category` | Optional grouping |
| `description` | Optional |
| `min_soil_moisture` / `max_soil_moisture` | 0–100 |
| `min_temperature` / `max_temperature` | °C |
| `min_soil_ec` / `max_soil_ec` | Fertility proxy (µS/cm) |
| `min_env_humidity` / `max_env_humidity` | Optional |
| `preferred_ph_min` / `preferred_ph_max` | Optional |
| `water_requirement` | `low` / `medium` / `high` (sets watering interval if hours blank) |
| `nutrient_level` | Optional categorical |
| `sunlight` | Optional |
| `watering_frequency_hours` | Overrides categorical water need |
| `source` | e.g. `openplantbook`, `edible_plant_db`, `openplantdb`, `manual` |
| `source_url` | Optional |
| `is_active` | `1` or `0` (default 1) |

Upsert key: `(source, external_pid)` when `external_pid` is set; otherwise insert-only by `common_name` + `source`.

## Suggested data sources

- [Open Plantbook](https://open.plantbook.io) — soil moisture / temp / EC ranges (API keys required for live fetch; export CSV offline)
- Edible Plant DB / GROW, openplantdb, plant-variety-database — good for names/species; map thresholds separately

Do not commit API credentials. Keep large dumps out of git unless intentional.
