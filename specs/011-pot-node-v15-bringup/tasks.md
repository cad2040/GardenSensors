# Tasks: 011-pot-node-v15-bringup

**Input**: Design documents from `/specs/011-pot-node-v15-bringup/`  
**Status**: Planned — tasks unchecked until physical bring-up completes

## Phase 1: Print STLs
- [ ] T001: Print pot node body (`hardware/enclosure/pot_node_body_v1.stl`) in PETG  
- [ ] T002: Print pot node lid (`hardware/enclosure/pot_node_lid_v1.stl`) in PETG  
- [ ] T003: Print gasket (`hardware/enclosure/pot_node_gasket_v1.stl`) in TPU  
- [ ] T004: Print USB cap (`hardware/enclosure/pot_node_usb_cap_v1.stl`) in TPU  

## Phase 2: Enclosure Leak Test
- [ ] T005: Assemble enclosure with gasket and USB cap  
- [ ] T006: Verify enclosure water tightness  

## Phase 3: Bench Assembly
- [ ] T007: Review assembly instructions in `docs/setup/POT_NODE_ASSEMBLY.md`  
- [ ] T008: Review hardware BOM in `docs/setup/HARDWARE_BOM.md`  
- [ ] T009: Assemble pot node hardware using enclosure parts  
- [ ] T010: Test electrical connections and sensor interfaces  

## Phase 4: Firmware Preparation
- [ ] T011: Flash NodeMCU board with `arduino/sketch_SoilSensor.ino`  

## Phase 5: First HTTP Ingest Verify
- [ ] T012: Send soil sensor data to garden stack HTTP endpoint  
- [ ] T013: Confirm successful data transmission in server logs  

## Phase 6: Close Lid
- [ ] T014: Secure lid in place with proper torque settings  
- [ ] T015: Inspect final assembly for any leaks or issues  

## Notes:
- Stop here for human review before proceeding further