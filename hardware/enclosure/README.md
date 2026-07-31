# Pot-node enclosure (V1.1 — weatherproof seal)

OpenSCAD model for the outdoor plant-pot IoT housing. Matches [`docs/setup/HARDWARE_BOM.md`](../../docs/setup/HARDWARE_BOM.md).

## Seal architecture

```
┌──────── flange (M3 screws / inserts) ────────┐
│  ┌──── sealed rim + gasket + lid tongue ───┐ │
│  │         dry electronics cavity          │ │
│  └─────────────────────────────────────────┘ │
└──────────────────────────────────────────────┘
              ↓ bottom M12 gland only
```

- **Screws outside the gasket** — no thread path into the dry volume  
- **No USB sidewall hole** by default (`usb_c_port = false`) — charge with lid open indoors  
- **Zip-tie slots on the pot clip**, not through the cavity wall  
- **Gland** = only cable penetration (use IP68 gland + sealant)

## Files

| File | Purpose |
|------|---------|
| `pot_node_enclosure_v1.scad` | Body, lid, gasket, preview |

## Export STLs

```bash
cd hardware/enclosure
openscad -D 'part="body"'   -o pot_node_body_v1.stl   pot_node_enclosure_v1.scad
openscad -D 'part="lid"'    -o pot_node_lid_v1.stl    pot_node_enclosure_v1.scad
openscad -D 'part="gasket"' -o pot_node_gasket_v1.stl pot_node_enclosure_v1.scad
```

Or use [OpenSCAD Playground](https://ochafik.com/openscad2) — paste the file, set `part`, render.

## Print guidance

| Part | Material | Notes |
|------|----------|--------|
| Body + lid | PETG / ASA | ≥3 perimeters, 4+ top/bottom; print lid outer-face down |
| Gasket | TPU 95A | Soft enough to compress ~0.3–0.5 mm under lid torque |
| Inserts | M3×5–6 mm brass heat-set | 4× in **flange** bosses only |

## Honest rating

With TPU gasket, even screw torque, and a sealed M12 gland this is aimed at **outdoor rain / splash** on a pot rim. It is **not** a certified IP67 product until you hose-test a print. Do not bury the enclosure or leave the USB option enabled outdoors.
