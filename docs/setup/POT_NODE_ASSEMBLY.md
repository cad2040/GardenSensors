# Pot-node assembly & wiring guide (V1.5)

Step-by-step build for the outdoor plant-pot sensor node: **locked BOM**, **V1.5 enclosure**, firmware in [`arduino/sketch_SoilSensor.ino`](../../arduino/sketch_SoilSensor.ino).

**Related:** [HARDWARE_BOM.md](HARDWARE_BOM.md) · [hardware/enclosure/README.md](../../hardware/enclosure/README.md) · [README.md](README.md) (pin map)

---

## Short answer: do I solder during install?

**Mostly no — solder on the bench first, then assemble.**

| Connection | Method | When |
|------------|--------|------|
| 18650 → holder | **No solder** — spring contacts | Insert cell in holder |
| Holder leads → TP4056 **B+ / B−** | **Solder** (or screw block if you add one) | **Bench**, before enclosure |
| TP4056 **OUT+ / OUT−** → MT3608 **IN+ / IN−** | **Solder** | **Bench** |
| MT3608 **OUT+ / OUT−** → shared **5 V / GND** pigtail | **Solder** (or Wago lever nut) | **Bench** |
| 5 V pigtail → NodeMCU **VIN** + probe **VCC** | **Dupont** (or solder direct) | Bench test, then plug in box |
| MT3608 / TP4056 / XY-017 **GND** → common GND | **Solder** into one ground bus, or Dupont to NodeMCU GND | **Bench** |
| XY-017 **VCC / GND** → NodeMCU **3V3 / GND** | **Dupont** (female–female on headers) | In enclosure (easy swap) |
| XY-017 **DI / RO** → NodeMCU **D6 / D7** | **Dupont** | In enclosure |
| XY-017 **A / B** → probe **A / B** | **Screw terminals** on module (if fitted) — no solder | After probe cable through gland |
| NodeMCU **D0** ↔ **RST** (deep sleep) | **Dupont jumper** or short soldered wire on pins | Before or during MCU install |
| Probe cable → gland | **Gland nut + seal** — no solder at gland | Last mechanical step |

**Rule of thumb:** solder **power paths** (battery → charger → boost → 5 V rail) once on a clean bench with a multimeter. Use **Dupont jumpers** for **NodeMCU ↔ XY-017** so you can unplug boards for flashing or debug. Do **not** solder the 18650 cell itself — only the holder’s flying leads.

---

## Tools & materials

- Multimeter (required for MT3608 trim)
- Soldering iron, 60/40 or lead-free solder, heat-shrink
- Dupont female–female jumpers (≥6) and optional 2.54 mm pin headers
- Small Philips screwdriver (MT3608 trim pot)
- M3 screws + 4× M3 heat-set inserts (flange)
- M12 IP68 gland + PTFE tape or silicone on threads
- TPU gasket + USB cap (printed)
- Optional: small lever-nut block (Wago 221-413) on the 5 V rail for service

---

## Wiring order (do this sequence)

Build and **test on the bench** through step 5 before closing the enclosure.

### Phase A — Power chain (bench, soldered)

Do this **outside** the case with the MT3608 **not** connected to the NodeMCU or probe yet.

```
18650 in holder
    │  (holder leads — red/black)
    ▼
TP4056  B+ / B−
    │
    OUT+ / OUT−  ──►  MT3608  IN+ / IN−
                            │
                            OUT+ / OUT−  ──►  5 V rail + common GND
```

1. **Insert 18650** in the Switch Electronics holder (correct polarity).
2. **Solder** holder **red → TP4056 B+**, **black → B−** (check silkscreen).
3. **Solder** **TP4056 OUT+ → MT3608 IN+**, **OUT− → IN−**.
4. **Trim MT3608** — adjust pot while measuring **OUT** with no load until **5.00 V ± 0.05 V**.
5. **Solder a short 5 V pigtail** from MT3608 OUT+ and OUT− (10–15 cm, 22–24 AWG). This is your **shared 5 V rail** for NodeMCU VIN and probe VCC later.

> **Charging note:** Many TP4056 “OUT” modules back-feed the load while USB is plugged in. Disconnect heavy loads or accept that the node may run from USB during charge. Do not charge a damaged cell.

### Phase B — Logic & sleep (bench or in case, mostly Dupont)

```
NodeMCU 3V3 / GND ──► XY-017 VCC / GND
NodeMCU D6 / D7   ──► XY-017 DI / RO   (TX→DI, RX←RO)
NodeMCU D0        ──► RST              (deep sleep wake)
```

6. Install **D0 ↔ RST** bridge (Dupont jumper is fine).
7. **Do not power XY-017 from 5 V** — use **3V3** unless your module silkscreen explicitly allows 5 V logic.
8. Prepare four Dupont leads: **3V3, GND, D6, D7** at the NodeMCU end; **VCC, GND, DI, RO** at the XY-017 end.

### Phase C — Load connections (after 5 V verified)

9. **NodeMCU VIN** → 5 V pigtail **+** · **GND** → common **−**.
10. **Probe VCC** → same 5 V **+** · **probe GND** → common **−** (leave probe disconnected until gland is fitted, or use a bench supply for first flash).
11. **XY-017 A / B** → probe **A / B** (match colours; swap A/B if Modbus is silent).

### Phase D — Bench test (before enclosure)

12. Flash [`arduino/sketch_SoilSensor.ino`](../../arduino/sketch_SoilSensor.ino); set WiFi/credentials.
13. Power from battery (or USB into TP4056 for bench only); confirm serial / dashboard readings.
14. Fix MT3608 voltage again if the trim drifted under load.

Only after steps 1–14 succeed → mechanical install below.

---

## Mechanical install order (V1.5 enclosure)

Open cavity, **lid off**, USB cap off. Follow this order so mounts and wiring stay accessible.

| Step | Action | Wiring in this step |
|------|--------|---------------------|
| 1 | Seat **18650 holder** in −X cradle | Power leads already soldered to TP4056 — dress leads toward +X shelf |
| 2 | Snap **NodeMCU** into floor bay | Plug **VIN/GND** from 5 V pigtail; **D0–RST** jumper |
| 3 | Snap **XY-017** into floor bay beside MCU | Dupont **3V3, GND, D6, D7** |
| 4 | Snap **MT3608** into +Y wall pocket | Confirm **IN** already wired from TP4056; **OUT** pigtail toward center |
| 5 | Slide **TP4056** onto +X USB shelf | Align Type-C with sidewall hole; **OUT** already goes to MT3608 |
| 6 | Route **probe cable** through **M12 gland** (+Y strip); tighten gland with sealant | **A/B** and **VCC/GND** to XY-017 / 5 V rail |
| 7 | Seat **TPU gasket**, **lid**, torque **M3** evenly; fit **USB cap** outdoors | — |

**Do not** install TP4056 or MT3608 before steps 2–3 — you need open access above the floor boards for Dupont wiring.

### Wire routing tips

- Keep **5 V probe + MCU** leads short and away from the ESP antenna side if possible.
- UART Duponts can run in the **6 mm gap** between NodeMCU and XY-017.
- Leave **~20 mm slack** under the TP4056 shelf for the charger/boost harness.
- Tie leads lightly with a small zip-tie to the cradle **outside** the cell (do not crush insulation).

---

## Power & pin reference (quick)

| From | To | Wire |
|------|-----|------|
| Holder + | TP4056 B+ | Red, soldered |
| Holder − | TP4056 B− | Black, soldered |
| TP4056 OUT+ | MT3608 IN+ | Soldered |
| TP4056 OUT− | MT3608 IN− | Soldered |
| MT3608 OUT+ | NodeMCU **VIN** + probe **VCC** | Pigtail / Dupont |
| MT3608 OUT− | NodeMCU **GND** + probe **GND** + XY-017 GND | Common ground |
| NodeMCU **3V3** | XY-017 **VCC** | Dupont |
| NodeMCU **D6** (TX) | XY-017 **DI** | Dupont |
| NodeMCU **D7** (RX) | XY-017 **RO** | Dupont |
| NodeMCU **D0** | NodeMCU **RST** | Jumper |
| XY-017 **A / B** | Probe **A / B** | Terminals |

Leave **D5** unwired (auto-flow RS485). See [HARDWARE_BOM.md](HARDWARE_BOM.md) for Modbus registers.

---

## Pre-close checklist

- [ ] MT3608 = **5.00 V** under load (or re-check after connect)
- [ ] Probe on **5 V**, not 3V3
- [ ] Common GND everywhere
- [ ] **D0–RST** bridged
- [ ] UART reads probe (moisture / temp / EC / pH)
- [ ] WiFi + firmware flashed **before** final lid torque
- [ ] Gland sealed; USB cap seated for outdoor use
- [ ] No bare 18650 tabs shorting the cradle rails

---

## Troubleshooting

| Symptom | Check |
|---------|--------|
| No 5 V | MT3608 trim, TP4056 OUT wiring, battery polarity |
| ESP boots, no Modbus | Swap A/B; baud 4800/9600; 3V3 to XY-017 |
| Resets in sleep | D0–RST bridge; insufficient battery under WiFi peak |
| USB charge but no run | OUT path through DW01A; load on OUT vs B pads per module variant |

---

## Related docs

- Parts list: [HARDWARE_BOM.md](HARDWARE_BOM.md)
- Enclosure print/export: [hardware/enclosure/README.md](../../hardware/enclosure/README.md)
- Dashboard deploy: [installation.md](installation.md) or `sudo ./setup.sh local`
