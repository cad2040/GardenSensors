# GardenSensors Development Session Summary

## Current Status: Bare-metal cleaned; Docker is the active automated path

### Done after resume (post hard-reset)

1. **Bare-metal local cleanup**
   - `CONFIRM=y sudo ./cleanup.sh local`
   - Removed `/var/www/html/garden-sensors` and Apache `garden-sensors` site
   - Dropped host DB artifacts / venv / vendor as before

2. **Docker automation in scripts**
   - `sudo ./setup.sh docker` — compose up --build, health wait, repair seed `user_id`, PHPUnit + pytest
   - `CONFIRM=y ./cleanup.sh docker` — `docker compose down -v`
   - Enhanced `cleanup.sh local` to tear down Apache vhost + web root when possible
   - `CONFIRM=y` skips interactive prompts

3. **Verified end-to-end**
   - Local cleanup OK (web root + apache site gone)
   - Docker cleanup + `setup.sh docker` completed
   - PHPUnit in container: passed
   - Pytest: 35 passed, 11 skipped

4. **Docs updated**
   - `docs/setup/docker.md`, `README.md`, `docs/setup/installation.md`
   - `docs/development/deployment.md`, `docs/development/TESTING.md`

### Commands

```bash
# Docker (preferred day-to-day)
sudo ./setup.sh docker
CONFIRM=y ./cleanup.sh docker

# Bare metal
sudo ./setup.sh local
CONFIRM=y sudo ./cleanup.sh local
```

### Pre-commit cleanup

- Deleted all agent `tmp_*` helper scripts
- Restored **202** files that only had whitespace/line-ending churn
- Updated `.gitignore`: ignore `tmp_*`; allow `.env.docker.example` and `docker/**/*.sql`
- Remaining dirty set is ~28 intentional Docker/local-fix files ready to stage
