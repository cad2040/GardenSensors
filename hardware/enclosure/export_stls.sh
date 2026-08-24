#!/usr/bin/env bash
# Regenerate pot-node enclosure STLs from pot_node_enclosure_v1.scad (V1.5)
set -euo pipefail
cd "$(dirname "$0")"
SCAD=pot_node_enclosure_v1.scad

if ! command -v openscad >/dev/null 2>&1; then
  echo "openscad not found — install OpenSCAD or use the committed .stl files as-is." >&2
  exit 1
fi

for part in body lid gasket usb_cap; do
  out="pot_node_${part}_v1.stl"
  echo "[*] Exporting $out ..."
  openscad -D "part=\"$part\"" -o "$out" "$SCAD"
done

echo "[*] Done:"
ls -lh pot_node_*_v1.stl
