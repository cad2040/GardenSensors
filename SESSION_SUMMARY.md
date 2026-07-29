# GardenSensors Development Session Summary

## Current Status: Plant catalog feature pushed; deploys torn down

### Delivered
- `plant_catalog` schema + migration `003`, `plants.catalog_id`
- Opt-in import via `SEED_PLANT_CATALOG=1` + `plant_catalog.seed.csv` (8 demo presets)
- Truncate/cleanup wipes catalog with other seed data
- Add/Edit Plant catalog dropdown + `get_plant_catalog.php` + PHPUnit coverage
- Fixed truncated `setup_docker` / `cleanup_docker` names and cleanup `CONFIRM=y`/`read` bug

### Tear-down (done)
```bash
CONFIRM=y ./cleanup.sh docker
CONFIRM=y sudo -E ./cleanup.sh local
```

### Redeploy later
```bash
SEED_PLANT_CATALOG=1 sudo -E ./setup.sh docker   # http://localhost:8080
# or bare-metal (from Windows use WSL IP, not 127.0.0.1:80)
SEED_PLANT_CATALOG=1 sudo -E ./setup.sh local
```
