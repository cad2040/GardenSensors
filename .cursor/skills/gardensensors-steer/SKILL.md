---
name: gardensensors-steer
description: >-
  Steers development for the GardenSensors (cad2040/GardenSensors) IoT garden
  monitoring repo: PHP dashboard (public/ + src/), MySQL, Python plots, Arduino
  firmware, setup.sh local/test/production deploys. Use when working in
  GardenSensors, on local deployment, PHPUnit, Python tests, setup.sh,
  cleanup.sh, dashboard plots, plants/sensors, or continuing prior GardenSensors
  agent sessions.
---

# GardenSensors development steering

## Read first

1. Skim [reference.md](reference.md) for recurring themes from past agent chats.
2. Confirm which workspace root is open (Windows clone vs WSL). Prefer the WSL
   tree when they diverge — it tracks origin more closely.
3. Prefer **extending** `setup.sh` / `cleanup.sh` and existing `docs/` over new
   one-off deploy scripts.

## Project map

| Layer | Location | Notes |
|-------|----------|--------|
| Web entry | `public/` | Dashboard, auth, plant/sensor CRUD, `public/api/` |
| PHP domain | `src/` | PSR-4 `GardenSensors\`: Models, Controllers, Services, Core, Config |
| Schema | `database/` | `schema.sql`, migrations, seed/cleanup SQL, `setup_db.sh` |
| Deploy | `setup.sh`, `cleanup.sh` | Modes: `local`, `test`, `production` |
| Tests | `tests/` | PHPUnit; also `tests/run_tests.sh`, Python tests under suite |
| Python | `python/` | Plotting and related tooling (venv / `.venv`) |
| Firmware | `arduino/` | Sensor sketches |
| Docs | `docs/setup`, `docs/development`, `docs/api` | Install, testing, deployment |
| Session notes | `SESSION_SUMMARY.md` | Latest completed work; refresh when closing large tasks |

Config: copy `.env.example` → `.env` (and `.env.test` for test mode). Never commit real `.env` files.

## Development priorities (in order)

1. **Reuse the established local deploy** — `sudo ./setup.sh local` (and
   `./cleanup.sh` when resetting). Do not add parallel local-deployment trees;
   past chats repeatedly created, cleaned, purged, and removed them.
2. **Keep tests green with deploy** — Local setup runs PHP + Python tests and
   fails deploy on failure. After behavior changes, run the relevant suite
   (`composer test` / `vendor/bin/phpunit`, or `tests/run_tests.sh`) before
   calling work done.
3. **Dashboard/API contract** — Plot and summary endpoints (`public/api/plot.php`,
   `public/api/summary.php`) must return expected JSON; prefer robust parsing over
   fragile stdout assumptions when calling Python.
4. **Schema + models together** — Prefer `database/schema.sql` and migrations;
   keep Models (`Plant`, `Sensor`, `Reading`, etc.) and `plant_sensors` linking
   aligned. Do not reintroduce removed patterns (e.g. deprecated `fact_plants`).
5. **Docs accuracy** — When install/cron/API paths change, update the matching
   file under `docs/` (and README quick start if needed).
6. **Firmware vs backend** — Keep Arduino changes separate unless integrating a
   new payload or endpoint.

## Dual workspace (Windows + WSL)

Often opened as a native Windows folder and as a WSL remote workspace. Pick
**one** canonical copy per task. If the repo will not load or remote reads
cancel, ensure WSL is running and reopen from the environment that holds the
up-to-date tree. Do not mix Windows and Linux paths in scripts.

## Local setup checklist

```
- [ ] .env and .env.test created from .env.example (local values only)
- [ ] sudo ./setup.sh local   # or cleanup.sh then setup when resetting
- [ ] Confirm web UI and dashboard plots load
- [ ] PHPUnit / deploy test step green
- [ ] Python venv available for plot scripts
```

Production/test: `sudo ./setup.sh production` or `sudo ./setup.sh test` per README.

## Testing

- PHP: `composer test` or `vendor/bin/phpunit` (bootstrap via `vendor/autoload.php`).
- Broader: `tests/run_tests.sh` when validating full stack.
- Do not hardcode machine-specific DB passwords into new files; use env / `.env.test`.
- Seed/cleanup SQL under `database/` supports deterministic dashboard checks.

## Code conventions

- New PHP domain logic goes in `src/` with existing namespace layout.
- Web-facing scripts live under `public/`; reuse `public/includes` and Controllers.
- Match existing style; small diffs that fix one layer beat broad rewrites.
- Security pieces (auth, rate limiting, CSRF where present) — reuse, do not bypass.

## Session continuity

When continuing an old thread:

- Check `SESSION_SUMMARY.md` and recent commits before re-scaffolding setup.
- Do not re-run full production install if local deploy already works.
- Ask what is left incomplete only if the request is ambiguous.

## Out of scope unless asked

- New cloud/hosting providers
- Replacing MySQL or the Arduino stack
- Large UI redesign unrelated to current dashboard patterns
- Committing `.env`, credentials, or real mail/API secrets

## Additional resources

- Chat history themes and anti-patterns: [reference.md](reference.md)
