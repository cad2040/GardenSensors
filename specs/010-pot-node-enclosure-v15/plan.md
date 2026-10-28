# Implementation Plan: 010-pot-node-enclosure-v15

**Branch**: `010-pot-node-enclosure-v15` | **Date**: retrospective | **Spec**: [spec.md](./spec.md)  
**Status**: Delivered

## Summary

Lock the outdoor pot-node hardware documentation and deliver a print-ready weatherproof enclosure: BOM, OpenSCAD design through V1.5 fit, committed STLs, and bench assembly guide.

## Technical Context

**Language/Version**: OpenSCAD, Markdown docs  
**Primary Dependencies**: Bambu/slicer toolchain (external), `export_stls.sh`  
**Storage**: N/A (design files in git)  
**Testing**: Geometry audit / fit-check (manual); physical hose-test deferred to `011`  
**Target Platform**: 3D printer (PETG/TPU) + bench assembly  
**Constraints**: Constitution — hardware changes need BOM/docs alignment; no secrets in firmware docs

## Project Structure

```text
docs/setup/HARDWARE_BOM.md
docs/setup/POT_NODE_ASSEMBLY.md
hardware/enclosure/pot_node_enclosure_v1.scad
hardware/enclosure/*.stl
hardware/enclosure/export_stls.sh
hardware/enclosure/README.md
```

**Structure Decision**: CAD + STLs under `hardware/enclosure/`; human procedures under `docs/setup/`.

## Implementation Notes (as shipped)

- V1.3/V1.4 issues fixed in V1.5: cavity size, XY-017 vs MCU stacking, TP4056 short-edge USB shelf.
- Cavity ~80×70×96 mm; side-by-side floor layout for 18650 | NodeMCU | XY-017.
- USB-C cap + gasket for hose-test readiness (physical verification in `011`).
