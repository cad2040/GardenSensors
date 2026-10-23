# 001-pot-node-v1.5-bringup

## Print Path
- Use the committed STLs for the pot-node enclosure.
  - PETG body and lid.
  - TPU gasket and USB cap.
- Export STLs using `export_stls.sh` only if there are changes in the `.scad` files (expect no CAD changes).

## Hose-Test
- Ensure the USB-C cap is properly seated.
- Pass criteria: The enclosure should be watertight.
- Fail criteria: Any leaks or water penetration.

## Bench Assembly Order
- Follow the steps in `POT_NODE_ASSEMBLY.md`:
  - Soldering vs. Dupont connections.
  - Wiring order.
  - Pre-close checklist.
- Flash the firmware to the NodeMCU **before** the final lid torque.

## Firmware
- Sketches live in the `arduino\sketch_SoilSensor.ino` directory.
- Flash the target NodeMCU.
- Configuration needed for WiFi/HTTP ingest (do not commit secrets).

## First Ingest Verify
- Confirm the HTTP ingest hit the garden stack:
  - Check logs.
  - Verify the UI.
  - Ensure the data is stored in the database.
- HTTP-only ingest; no MySQL LAN exposure.

## Risks / Rollback
- Print fail: Ensure the STLs are correct and the print is successful.
- Leak: Ensure the enclosure is watertight.
- Bad RS485 wiring: Verify the RS485 connections.
- Failed ingest: Ensure the data is successfully ingested via HTTP.

## Verification Commands/Checks
- On AwesomeX:
  - Check logs for print success.
  - Verify the enclosure is watertight.
  - Flash the firmware and ensure it is successful.
- On-bench:
  - Check the enclosure for leaks.
  - Verify the NodeMCU firmware is flashed correctly.
  - Confirm the HTTP ingest is successful.




















































