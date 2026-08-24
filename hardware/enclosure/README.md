# Pot-node enclosure (V1.5 — BOM USB-C + installable mounts)

OpenSCAD model for the outdoor plant-pot IoT housing. Matches [`docs/setup/HARDWARE_BOM.md`](../../docs/setup/HARDWARE_BOM.md).

**Assembly & wiring (solder vs Dupont, step order):** [`docs/setup/POT_NODE_ASSEMBLY.md`](../../docs/setup/POT_NODE_ASSEMBLY.md)

## Seal architecture

```
┌──────── flange (M3 screws / inserts) ────────┐
│  ┌──── sealed rim + gasket + lid tongue ───┐ │
│  │  floor: [18650] [NodeMCU] [XY-017]      │ │
│  │  +Y wall: [MT3608]   +X shelf: [TP4056] │ │
│  │  USB-C ← short-edge (BOM) + TPU cap     │ │
│  └─────────────────────────────────────────┘ │
└──────────────────────────────────────────────┘
         ↓ M12 gland (+Y service strip)
```

- **Screws outside the gasket** — no thread path into the dry volume  
- **USB-C sidewall with sealable cap** — matches BOM TP4056 (**USB on short edge**)  
- **Zip-tie slots on the pot clip**, not through the cavity wall  
- **Gland** offset into the +Y strip (not under the MCU)  
- **Preview colour** — dark green body/lid

## Sizing (V1.5)

| | V1.4 | **V1.5** |
|--|------|----------|
| Sealed cavity | 64 × 58 × 92 mm | **80 × 70 × 96 mm** |
| Outer flange footprint | ~86.8 × 80.8 mm | **~102.8 × 92.8 mm** |

## Why the mounts changed

V1.4 stacked XY-017 **above** the NodeMCU, so the raised bay blocked dropping the MCU in. V1.5 puts **all floor boards side-by-side with open tops**, MT3608 on the **+Y wall**, and TP4056 on an **elevated +X shelf** (install last).

## Internal mounts

| Part | Mount | Insert |
|------|--------|--------|
| **18650 holder** | Tall cradle + lips + mid fingers | From above |
| **NodeMCU** | Floor clip bay (48 mm along X) | From above |
| **XY-017** | Floor clip bay beside MCU (+Y), **not stacked** | From above |
| **MT3608** | +Y wall pocket (components face into cavity) | After floor boards |
| **TP4056** | Elevated short-edge USB shelf → sidewall hole | **Last** (slide toward +X) |

Fit clearance: `mount_clear` = **0.45 mm** (increase if PETG prints tight).

Wiring gaps: **5 mm** battery↔MCU, **6 mm** MCU↔XY (UART), **~23 mm** under TP4056 shelf for harness.

## Install order (required)

1. Seat **18650 holder** in the −X cradle.  
2. Snap **NodeMCU** into its floor bay; dress antenna/USB away from rails.  
3. Snap **XY-017** into the adjacent floor bay; wire UART (**D6/D7**) and 3.3 V/GND while access is open.  
4. Snap **MT3608** into the +Y wall pocket; set **5.00 V** before connecting loads.  
5. Wire battery → TP4056 pads / MT3608 input as needed, then slide **TP4056** onto the +X shelf until Type-C meets the wall hole.  
6. Route probe cable through the **M12 gland** (+Y strip); fit gasket + lid; seat USB cap outdoors.

Do **not** install TP4056 or MT3608 before the floor boards — you need that access for wiring.

See **[POT_NODE_ASSEMBLY.md](../../docs/setup/POT_NODE_ASSEMBLY.md)** for the full wiring order, bench-test steps, and what to solder vs plug in.

## Files

| File | Purpose |
|------|---------|
| `pot_node_enclosure_v1.scad` | Parametric source (body, lid, gasket, USB cap, preview) |
| `pot_node_body_v1.stl` | **Ready to print** — body (PETG/ASA) |
| `pot_node_lid_v1.stl` | **Ready to print** — lid (PETG/ASA) |
| `pot_node_gasket_v1.stl` | **Ready to print** — lid gasket (TPU 95A) |
| `pot_node_usb_cap_v1.stl` | **Ready to print** — USB sidewall cap (TPU 95A) |

**Bambu / slicer handoff:** import the four `.stl` files into Bambu Studio (or any slicer). No OpenSCAD required for printing.

Regenerate STLs after editing the `.scad`:

```bash
cd hardware/enclosure
./export_stls.sh
```

Or manually:

```bash
cd hardware/enclosure
openscad -D 'part="body"'    -o pot_node_body_v1.stl    pot_node_enclosure_v1.scad
openscad -D 'part="lid"'     -o pot_node_lid_v1.stl     pot_node_enclosure_v1.scad
openscad -D 'part="gasket"'  -o pot_node_gasket_v1.stl  pot_node_enclosure_v1.scad
openscad -D 'part="usb_cap"' -o pot_node_usb_cap_v1.stl pot_node_enclosure_v1.scad
```

Or use [OpenSCAD Playground](https://ochafik.com/openscad2) — paste the `.scad`, set `part`, render.

## Print guidance

| Part | Material | Notes |
|------|----------|--------|
| Body + lid | PETG / ASA — **dark green** recommended | ≥3 perimeters, 4+ top/bottom; print lid outer-face down |
| Lid gasket | TPU 95A | Compress ~0.3–0.5 mm under lid torque |
| USB cap | TPU 95A (preferred) | Press into sidewall recess |
| Inserts | M3×5–6 mm brass heat-set | 4× in **flange** bosses only |

## USB-C use

BOM TP4056 has Type-C on the **short edge** — the V1.5 shelf matches that. Keep the USB cap seated outdoors; pull only to charge.

## Honest rating

Aimed at **outdoor rain / splash** on a pot rim — not certified IP67 until hose-tested with the USB cap fitted. Do not bury the enclosure.
