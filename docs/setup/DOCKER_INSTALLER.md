# Docker-based installer (thin wizard)

Cross-platform install path for end users: **Docker Desktop / Engine + Compose**, then a first-run admin form.

## Prerequisites

- [Docker](https://docs.docker.com/get-docker/) (Desktop on Windows/macOS, Engine on Linux)
- Compose v2 (`docker compose`)

## Quick start

```bash
git clone https://github.com/cad2040/GardenSensors.git
cd GardenSensors
./scripts/docker-install.sh
```

The script:

1. Checks Docker / Compose  
2. Creates `.env` from `.env.example` if missing  
3. Asks for admin username, email, password, HTTP port  
4. Writes a local `docker-compose.override.yml` for the port  
5. Runs `docker compose up -d --build`  
6. Calls `/setup/first_run.php` (or open `/setup/` in the browser)

Then open `http://localhost:8080/` (or your chosen port).

## First-run web UI

If the script cannot POST automatically:

1. Open `http://localhost:PORT/setup/`  
2. Create admin account  
3. A lock file `storage/setup.lock` prevents re-running  

To intentionally reset first-run (dev only): delete `storage/setup.lock`.

## After install

1. Set SMTP in `.env`: `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`  
2. Restart web container if needed  
3. Sign in → **Settings** → enable alerts → **Send test email**  
4. Ensure alert cron runs (host crontab or a compose `cron` service — see Priority A notes in `SESSION_SUMMARY.md`)

## Native Win/Mac installers later

This thin wizard is the supported path. Signed Electron/Tauri launchers can wrap `docker-install.sh` later without changing the stack.
