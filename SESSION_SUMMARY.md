# GardenSensors Development Session Summary

**Last updated:** 2026-07-31

## Current status: Outdoor pot-node hardware + weatherproof enclosure ready to push

Locked **V1.0 PoC BOM** (NodeMCU + CWT/JXCT 4-in-1 RS485 + XY-017 + TP4056/18650/MT3608) and **V1.1 weatherproof enclosure** CAD (flange screws outside seal, TPU gasket, no USB sidewall hole). Docs and OpenSCAD are in-repo; print/hose-test still pending. Software Priority A (alerts/SMTP/cron) unchanged.

Default admin after seed: **`admin` / `admin123`**.

---

## Completed this session (2026-07-31)

### Hardware BOM (V1.0 PoC)

- [`docs/setup/HARDWARE_BOM.md`](docs/setup/HARDWARE_BOM.md) — locked parts, footprints, vendor notes
- Wiring/power updated in [`docs/setup/README.md`](docs/setup/README.md) + root README / installation pointers
- Stack: NodeMCU VIN from **MT3608 @ 5.0 V**; probe on same 5 V rail; XY-017 **auto-flow** (no DE); D0↔RST deep sleep

### Enclosure CAD (V1.1 weatherproof seal)

- [`hardware/enclosure/pot_node_enclosure_v1.scad`](hardware/enclosure/pot_node_enclosure_v1.scad) + [`hardware/enclosure/README.md`](hardware/enclosure/README.md)
- Continuous gasket + lid tongue; **M3 screws on outer flange** (outside seal path)
- `usb_c_port = false` by default — charge TP4056 with **lid open indoors**
- Zip-tie slots on external pot clip; bottom **M12** gland only cable entry
- Aim: rain/splash resistant with good assembly — not certified IP67 until hose-tested

### Charge model

Rechargeable via TP4056 USB-C; sealed enclosure requires opening the lid (service charge), not an outdoor sidewall USB hole.

---

## Earlier (2026-07-30) — already on origin

EC nutrient proxy, plant catalog, Docker path — see `da0a2a0` / `6c10fc4` / `84cde88`.

---

## Next steps

### Hardware follow-ups

1. Print body/lid (PETG/ASA) + TPU gasket; heat-set M3 inserts in flange
2. Hose / rain test assembly with sealed M12 gland
3. Optional: export STLs from OpenSCAD CLI or Playground
4. Optional later: sealed USB cover if outdoor plug-in is required (`usb_c_port = true`)

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

- Branch: `master`
- Local deploy: **torn down** (last noted)
- Today’s push: hardware BOM + enclosure V1.1 + docs/session summary

**Next session focus:** print/test enclosure **or** Priority A (notifications + settings + SMTP + cron).
