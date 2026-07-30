# GardenSensors Development Session Summary

**Last updated:** 2026-07-30

## Current status: EC nutrient proxy ready to push; local deploy torn down

Stage-1 horticultural framing is in place: soil **EC as nutrient proxy** (not fake nitrate), per-plant dashboard tiles, side-panel plot legend, and deploy/test wiring. Work is rebased onto current `origin/master` (Docker Compose path + plant catalog already upstream). Local Apache/MySQL deployment was torn down after validation.

Default admin after seed: **`admin` / `admin123`**.

---

## Completed this session (2026-07-30)

### EC nutrient proxy (nitrogen → EC)

- Available nutrient EC = `max(0, soil_ec − baseline_water_ec)` + rolling trend → `underfed` | `balanced` | `overfed`
- `src/Services/EcNutrientService.php`
- Migrations `004_ec_nutrient_proxy.sql`, `005_user_settings.sql`
- Plant EC zones; user `baseline_water_ec` + `ec_alert`
- Firmware posts Modbus EC mS/cm; docs: `docs/development/ec-nutrient-proxy.md`, `docs/setup/README.md`
- Dashboard tiles + summary API; seed `database/seed_test_plot_data.sql`
- Tests/CI/setup/sync updated; plot legend in Bokeh right panel + `stretch_width`

### Deploy notes discovered

- Fresh deploy needed `user_settings` (added); plant tiles failed without it
- Alert email path is **not** production-ready yet (see Priority A below)

---

## Next steps

### Priority A — Stage 1: reliable alerts + email (1–2 pots, notify only)

**Gaps:** no `notifications` table in schema; Settings UI vs `user_settings` JSON mismatch; cron uses bare `mail()` (SMTP not wired); cron install not guaranteed; dual Notification implementations.

**Plan:**

1. Add `notifications` table + migration `006_notifications.sql`
2. Wire Settings UI to `user_settings` (moisture/temp/pH/EC alerts, email, `baseline_water_ec`)
3. Align alert engines (moisture + temperature + pH + EC) on the cron code path; keep cooldown
4. Configurable SMTP (env) or document MTA; optional test-email button
5. `setup.sh` installs crontab for `check_alerts.php`
6. PHPUnit + mocked-mail cron integration tests

**Out of scope for A:** valves / pumps / auto-water.

### Priority B — Stage 2: optional auto-watering

**Gaps:** `RunPump.py` is manual GPIO only; no per-plant `auto_water` flag; alerts not linked to pump.

**Plan:**

1. Model: `auto_water`, pump pin/id, safety caps; keep `water_amount` + calibration → seconds
2. UI: per-plant auto-water off by default; last watered / next eligible
3. Job: moisture below min + `auto_water` → pump → update `last_watered`; fail closed off-Pi / stale data
4. Tests with mocked GPIO

### Deferred

- Further Docker polish (Compose already on origin)
- Claiming nitrate from EC; on-device ESP trend SM

---

## Redeploy

```bash
sudo ./cleanup.sh test
sudo ./setup.sh local
```

---

## Repository status

- Branch: `master` (aligned with `origin/master` + EC commit pending)
- Local deploy: **torn down**
- Ready to push after commit

**Next session focus:** Priority A (notifications + settings + SMTP + cron), then stage-1 pot notify; Priority B when watering hardware starts.
