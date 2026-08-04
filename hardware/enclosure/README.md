# Pot-node enclosure (V1.3 — sealed USB-C + secure mounts)

OpenSCAD model for the outdoor plant-pot IoT housing. Matches [`docs/setup/HARDWARE_BOM.md`](../../docs/setup/HARDWARE_BOM.md).

## Seal architecture

```
┌──────── flange (M3 screws / inserts) ────────┐
│  ┌──── sealed rim + gasket + lid tongue ───┐ │
│  │  [18650 cradle]  [clip bays: MCU/RS/…]  │ │
│  │    [USB-C] ← recessed face + press-in  │ │
│  │              sealed cap (TPU)           │ │
│  └─────────────────────────────────────────┘ │
└──────────────────────────────────────────────┘
              ↓ bottom M12 gland
```

- **Screws outside the gasket** — no thread path into the dry volume  
- **USB-C sidewall with sealable cap** (`usb_c_port = true`) — charge without removing the lid; keep the cap seated outdoors  
- **Zip-tie slots on the pot clip**, not through the cavity wall  
- **Gland** = primary cable penetration (use IP68 gland + sealant)  
- **Preview colour** — dark green body/lid (`body_color` / `lid_color`)

## Internal mounts (V1.3)

| Part | Mount |
|------|--------|
| **18650 holder** | Tall cradle + inward lips + mid-height retention fingers |
| **NodeMCU** | Snap-fit clip bay (side rails + overhang lips + end stops) |
| **XY-017 RS485** | Clip bay on pillars above the NodeMCU |
| **TP4056** | Edge pocket against the USB sidewall (aligns with port) |
| **MT3608** | Clip bay in the upper electronics bay |

Boards **press in** from above (or slide into the TP4056 edge pocket). Fit clearance is `mount_clear` (default **0.45 mm**) — increase if your PETG prints tight.

## Files

| File | Purpose |
|------|---------|
| `pot_node_enclosure_v1.scad` | Body, lid, gasket, USB cap, preview |

## Export STLs

```bash
cd hardware/enclosure
openscad -D 'part="body"'    -o pot_node_body_v1.stl    pot_node_enclosure_v1.scad
openscad -D 'part="lid"'     -o pot_node_lid_v1.stl     pot_node_enclosure_v1.scad
openscad -D 'part="gasket"'  -o pot_node_gasket_v1.stl  pot_node_enclosure_v1.scad
openscad -D 'part="usb_cap"' -o pot_node_usb_cap_v1.stl pot_node_enclosure_v1.scad
```

Or use [OpenSCAD Playground](https://ochafik.com/openscad2) — paste the file, set `part`, render.

## Print guidance

| Part | Material | Notes |
|------|----------|--------|
| Body + lid | PETG / ASA — **dark green** recommended | ≥3 perimeters, 4+ top/bottom; print lid outer-face down |
| Lid gasket | TPU 95A | Compress ~0.3–0.5 mm under lid torque |
| USB cap | TPU 95A (preferred) | Press into sidewall recess |
| Inserts | M3×5–6 mm brass heat-set | 4× in **flange** bosses only |

## Assembly order (electronics)

1. Seat **18650 holder** in the −X cradle (press past mid fingers).  
2. Snap **NodeMCU** into the floor clip bay.  
3. Snap **XY-017** into the raised bay above it.  
4. Slide **TP4056** into the USB-wall pocket so Type-C lines up with the cutout.  
5. Snap **MT3608** into the upper bay; trim to **5.00 V** before connecting loads.  
6. Route probe cable through the M12 gland; fit lid gasket + lid; seat USB cap outdoors.

## USB-C use

Keep the USB cap seated outdoors. Pull only to charge, then reseat. Cap off + rain = water path into the cavity.

## Honest rating

Aimed at **outdoor rain / splash** on a pot rim — not certified IP67 until hose-tested with the USB cap fitted. Do not bury the enclosure.
