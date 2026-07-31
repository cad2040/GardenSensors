# Hardware BOM — Outdoor plant-pot sensor node

**Status:** Locked **Version 1.0** (PoC / outdoor pot-clip enclosure)  
**Audience:** Builders assembling one ESP8266 node with RS485 soil probe and always-on 18650 power.

Canonical wiring: [`docs/setup/README.md`](README.md)  
Firmware: [`arduino/sketch_SoilSensor.ino`](../../arduino/sketch_SoilSensor.ino)  
**3D-printed enclosure:** [`hardware/enclosure/`](../../hardware/enclosure/) (`pot_node_enclosure_v1.scad`)

---

## Architecture (V1.0)

```
[TP4056 Type-C + DW01A] ← USB charge (side window or open lid)
         │
    [1× 18650 3.7 V in Switch Electronics 1× holder]
         │
    [MT3608 boost → 5.0 V regulated rail] ──┬── NodeMCU VIN
                                            ├── Probe VCC
                                            └── common GND
[NodeMCU V2 ESP8266] ← D0–RST → deep sleep wake
         │ UART (D6 TX / D7 RX)
[XY-017 / SP3485 auto-flow TTL↔RS485]
         │ A / B
[JXCT / ComWinTop 4-in-1 RS485 soil probe]
         │
   cable Ø ~4.5 mm through bottom M12×1.5 IP68 gland
```

---

## Bill of materials (locked) — verified footprints & vendors

| Role | Part | Footprint (L×W×H mm) | Vendor |
|------|------|----------------------|--------|
| **MCU** | NodeMCU V2 ESP-12F (CH340 / CP2102) | **48.0 × 25.0 × 13.0** | [eBay UK](https://www.ebay.co.uk/itm/205615495268) |
| **Soil probe** | JXCT / ComWinTop **4-in-1** RS485 Modbus (DC **5–30 V**) | IP68 epoxy + 316 SS prongs; cable OD **~4.5 mm** | [AliExpress](https://www.aliexpress.com/item/1005005684119688.html) |
| **RS485** | **XY-017** / **SP3485** auto-flow TTL↔RS485 (3.3 V logic) | **43.0 × 15.0 × 12.0** | [eBay UK](https://www.ebay.co.uk/itm/257136577348) |
| **Charger** | **TP4056** USB **Type-C** + **DW01A** protection | **26.0 × 17.0 × 4.5** | [eBay UK](https://www.ebay.co.uk/itm/356193172042) |
| **Boost** | **MT3608** 2 A step-up (trim to **5.0 V**) | **36.0 × 17.0 × 14.0** (incl. pot) | [eBay UK](https://www.ebay.co.uk/itm/387403912111) |
| **Cell holder** | Switch Electronics **1×** 18650 holder, 150 mm leads | **78.0 × 21.0 × 20.0** | [eBay UK](https://www.ebay.co.uk/itm/265241814704) |
| **Cell** | 18650 3.7 V **~3400 mAh** flat-top | **Ø18 × 65** | [eBay UK](https://www.ebay.co.uk/itm/198535722863) |
| **Gland** | **M12 × 1.5** IP68 nylon | Panel hole **Ø12.0** (for 3–6.5 mm cable) | [eBay UK](https://www.ebay.co.uk/itm/267680335697) |

### Probe Modbus map (firmware)

| Register | Quantity |
|----------|----------|
| `0x0000` | Moisture |
| `0x0001` | Temperature |
| `0x0002` | EC (×0.01 → mS/cm typical) |
| `0x0003` | pH |

### Explicitly out of V1.0

| Item | Status |
|------|--------|
| Voltaic always-on USB banks | Optional premium path |
| DFRobot SEN0604 / SEN0605 | Compatible class; not the locked PoC SKU |
| Classic MAX485 with RE/DE on `D5` | Firmware-compatible; V1.0 uses **auto-flow** XY-017 |
| 18650 Battery Shield V3 as system PSU | Avoid (idle / standby) |
| Generic phone banks without always-on | Avoid |

---

## Critical electrical rules

1. **Common 5 V rail** — Probe **VCC** and NodeMCU **`VIN`** share the MT3608 **5.0 V** output and **common GND**.  
   **Do not** power the RS485 probe from ESP **3.3 V**.
2. **Calibrate first** — Set MT3608 to **exactly 5.0 V DC** (multimeter) **before** connecting `VIN` or the probe.
3. **Deep sleep** — Bridge **`D0` (GPIO16) → `RST`**.
4. **Charge path** — Type-C on TP4056 charges the 18650; loads run from MT3608 **5 V**.

---

## Suggested pin map (V1.0)

| Signal | NodeMCU | Module |
|--------|---------|--------|
| UART RX | **D7** (GPIO13) | XY-017 RO/RX |
| UART TX | **D6** (GPIO12) | XY-017 DI/TX |
| Direction | *unused* | Auto-flow |
| Deep sleep | **D0** ↔ **RST** | Wire bridge |
| 5 V in | **VIN** | MT3608 **5.0 V** |
| Transceiver VCC | **3V3** (typical) | Confirm silkscreen |
| GND | **GND** | Common |

Leave firmware **D5** unwired when using XY-017 auto-flow.

---

## Pre-flight checklist

- [ ] MT3608 = **5.00 V ±0.05 V** before connecting loads  
- [ ] Probe on **5 V** rail (not 3V3)  
- [ ] Common GND continuous  
- [ ] **D0–RST** bridge installed  
- [ ] XY-017 A/B match probe  
- [ ] Cable through **M12** gland; nut sealed  
- [ ] Flash / WiFi config before final lid torque  
- [ ] USB-C window plugged when outdoors (if cutout enabled)

---

## Enclosure (3D print)

| Item | Spec |
|------|------|
| Source | [`hardware/enclosure/pot_node_enclosure_v1.scad`](../../hardware/enclosure/pot_node_enclosure_v1.scad) (**v1.1 weatherproof seal**) |
| Sealed cavity | **54 × 44 × 90 mm** |
| Walls | **2.4 mm** |
| Seal | Continuous gasket groove on sealed rim; lid tongue compresses **1.5 mm** TPU/silicone |
| Fasteners | 4× **M3** heat-set inserts on an **outer flange** (screws **outside** the seal — do not pierce the dry volume) |
| Gland | **Ø12 mm** on **bottom** only; use IP68 gland + thread sealant / PTFE |
| USB | **No sidewall USB hole by default** — charge indoors with lid open (`usb_c_port = false`) |
| Mount | Rear rim clip (15–25 mm) + zip-tie slots on the **clip plate** (not through the cavity wall) |

Print / export: [`hardware/enclosure/README.md`](../../hardware/enclosure/README.md).

### Internal layout

| Zone | Contents |
|------|----------|
| −X bay | 18650 holder **vertical** (78 mm along height) |
| +X bay | NodeMCU, XY-017, MT3608, TP4056 |
| −Y | Pot rim clip + zip-tie slots (outside sealed volume) |
| Bottom | M12 gland (probe cable down into soil) |

### Why this seals better than a screw-through lid

Screws through a lid into cavity bosses create a water path along the threads. This design keeps the gasket as an unbroken ring around the electronics; clamp screws only compress the flange **outside** that ring.

### Weatherproofing assembly notes

1. Print gasket in TPU (or use a cut silicone cord in the groove).  
2. Seat gasket fully before placing lid; torque M3 screws evenly (don’t crush one corner).  
3. Install M12 gland with rubber washer inside; PTFE tape or silicone on threads.  
4. Keep `usb_c_port = false` for outdoor use.  
5. Expect **weather-resistant / splash / rain** performance with good assembly — not a lab-certified IP67 without testing.


---

## Related docs

- Wiring overview: [`docs/setup/README.md`](README.md)  
- EC nutrient proxy: [`docs/development/ec-nutrient-proxy.md`](../development/ec-nutrient-proxy.md)  
- Firmware: [`arduino/sketch_SoilSensor.ino`](../../arduino/sketch_SoilSensor.ino)
