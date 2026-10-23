# 001-pot-node-v15-bringup

## Goal
The goal of this feature is to bring up the first V1.5 outdoor pot-node, from the committed STLs through the first successful HTTP sensor ingest. This includes assembly, firmware flashing, and initial HTTP ingest verification.

## In Scope
- Assemble the pot-node using the committed STLs (body, lid, gasket, USB cap).
- Flash the firmware to the NodeMCU.
- Verify the first successful HTTP sensor ingest.

## Out of Scope
- Auto-watering functionality.
- Docker installer polish.
- SMTP wiring.
- New dashboard UI.

## User-Facing Outcome
- A human can assemble one pot-node following the provided documentation.
- The pot-node can successfully ingest sensor data via HTTP.

## Acceptance Criteria
- Print the pot-node enclosure.
- Hose-test the enclosure to ensure it is watertight.
- Assemble the pot-node following the assembly guide.
- Flash the firmware to the NodeMCU.
- Verify the first successful HTTP sensor ingest.

## Assumptions / Risks
- The enclosure material (PETG/TPU) will be watertight.
- The USB-C cap seal will be secure.
- The firmware will be flashed before the final torque on the lid.

## Non-Goals
- Implement auto-watering functionality.
- Polish the Docker installer.
- Wire the SMTP functionality.
- Develop a new dashboard UI.
