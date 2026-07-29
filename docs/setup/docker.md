# Docker Compose Workflow

Additive to bare-metal local deploy (`sudo ./setup.sh local`). Prefer the setup/cleanup script modes below for a gated deploy (build, health wait, PHPUnit + pytest).

## Prerequisites

- Docker Engine + `docker-compose` (v1.29+) or Compose V2 (`docker compose`)
- Ports **8080** (web) and **3307** (MySQL host publish) free

## Automated deploy / cleanup (recommended)

```bash
# Build, start, wait healthy, run PHP + Python tests
sudo ./setup.sh docker

# Stop containers and wipe volumes (destructive)
CONFIRM=y ./cleanup.sh docker
```

App URL: [http://localhost:8080](http://localhost:8080)  
Default login: `admin` / `password`

Non-interactive cleanups use `CONFIRM=y`.

## Manual compose commands

```bash
cp .env.docker.example .env.docker   # optional; compose has defaults
sudo docker-compose up -d --build
sudo docker-compose down             # stop (keep volume)
sudo docker-compose down -v          # stop + wipe MySQL volume
```

## Services

| Service | Role |
|---------|------|
| `web` | PHP 8.3 + Apache; DocumentRoot `public/`; Python venv at `/opt/gs-venv` for `api/plot.php` |
| `db` | MySQL 8.0; init from `database/schema.sql`, `create_sensor_readings_view.sql`, `docker/mysql/03_seed.sql` |
| `python` | Pytest image (`--profile tools`) |

## Environment

Inside the compose network, the app uses `DB_HOST=db`. Host-side MySQL publish is `127.0.0.1:3307`.

See [`.env.docker.example`](../../.env.docker.example).

## Tests in containers

`setup.sh docker` runs these automatically. Manually:

```bash
sudo docker-compose exec web ./vendor/bin/phpunit --testdox
sudo docker-compose --profile tools run --rm -e GUI_BASE_URL=http://web python pytest tests/python -v
```

## Local vs Docker

| Concern | Bare metal | Docker |
|---------|------------|--------|
| Start | `sudo ./setup.sh local` | `sudo ./setup.sh docker` |
| Reset | `CONFIRM=y sudo ./cleanup.sh local` | `CONFIRM=y ./cleanup.sh docker` |
| URL | `http://localhost/` (vhost) | `http://localhost:8080/` |
| Config | `.env` / `.env.test` | compose `environment` + optional `.env.docker` |
| Plots | host `.venv` | `/opt/gs-venv` (or `PYTHON_BIN`) |

Keep both paths; Docker does not replace bare-metal `local`/`production`/`test` modes.

## Troubleshooting

- **Plot 500 / python not found** — ensure web image built with `/opt/gs-venv`; `plot.php` also falls back to `python3` on `PATH`. Override with `PYTHON_BIN`.
- **DB not ready** — `setup.sh docker` waits for healthy `db` + login HTTP 200; raw `compose up` may need a short wait.
- **Empty plants / wrong owner** — schema seeds Tomato/Basil/Succulent without `user_id`; `03_seed.sql` and `setup.sh docker` set `user_id=1`. Fresh volume after seed changes: `CONFIRM=y ./cleanup.sh docker`.
- **Port conflict** — change published ports in `docker-compose.yml` or free 8080/3307.
- **Permission / volume mounts** — `public/`, `src/`, `python/` are bind-mounted for live edits; `vendor/` stays in the image.
