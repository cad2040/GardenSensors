// GardenSensors — Outdoor plant-pot IoT node enclosure (V1.5 — BOM USB-C + installable mounts)
// OpenSCAD parametric model for PETG / ASA (+ TPU gasket / USB cap)
//
// Seal strategy (IP-minded):
//   • Continuous gasket groove on the sealed rim
//   • Lid tongue compresses the gasket
//   • M3 screws sit on an OUTER flange — they never pierce the wet→dry path
//   • USB-C sidewall with recessed seal face + press-in cap (charge without opening lid)
//   • Zip-tie slots are on the EXTERNAL clip, not through the cavity wall
//   • Bottom M12 gland + thread sealant is the only cable penetration when USB capped
//
// Mounts (V1.5) — installable + wired:
//   • No board bay sits above another (V1.4 stacked XY over MCU blocked insertion)
//   • Floor: 18650 | NodeMCU | XY-017 side-by-side with open tops
//   • +Y wall: MT3608 pocket (install after floor boards)
//   • +X: TP4056 short-edge USB shelf (BOM Type-C) elevated — install last
//   • Gland offset into +Y service strip (not under MCU)
//
// Install order: battery → NodeMCU → XY-017 → (wire UART/power) → MT3608 → TP4056 → gland/lid
//
// Units: mm
// part = "body" | "lid" | "gasket" | "usb_cap" | "preview"
//
// Docs: docs/setup/HARDWARE_BOM.md

/* [Which part] */
part = "preview"; // ["body", "lid", "gasket", "usb_cap", "preview"]

/* [Sealed internal cavity — V1.5] */
// Floor: batt(21×20) + MCU(48×25) + XY(43×15) with gaps; +Y strip for MT3608 + gland
inner_w = 80.0;
inner_d = 70.0;
inner_h = 96.0;

/* [Shell] */
wall = 2.4;
corner_r = 3.0;
flange = 9.0;          // outer clamp flange (screws live here)

/* [Appearance — Customizer / preview only] */
body_color = [0.06, 0.28, 0.12];   // dark green
lid_color  = [0.08, 0.32, 0.14];

/* [Seal — tongue compresses 1.5 mm TPU / silicone] */
gasket_h = 1.5;
gasket_w = 2.2;
tongue_w = 1.5;
tongue_h = 2.0;        // compresses gasket (~0.3–0.5 mm squeeze when lid closed)
groove_play = 0.2;     // lateral clearance so tongue finds groove

/* [M3 heat-set inserts on flange (outside seal)] */
insert_od = 4.2;
insert_depth = 5.5;
boss_od = 9.0;
boss_h = 6.5;
m3_clear = 3.4;
m3_csink_d = 6.2;
m3_csink_h = 1.8;
flange_screw_inset = flange / 2;

/* [M12×1.5 IP68 gland — bottom, offset into +Y service strip] */
gland_hole_d = 12.0;
gland_boss_od = 18.0;
gland_boss_h = 3.5;

/* [USB-C sealed charge port — BOM TP4056 short-edge Type-C] */
usb_c_port = true;
usb_c_w = 9.2;
usb_c_h = 3.6;
usb_c_z = 42.0;          // elevated shelf clears floor boards + wiring
usb_seal_pad = 2.4;
usb_recess_depth = 1.2;
usb_cap_plug_extra = 0.6;
usb_cap_tab = 6.0;
usb_fit_clear = 0.3;

/* [Internal mounts] */
mount_clear = 0.45;
clip_lip = 1.15;
clip_rail_t = 1.8;
batt_rail_h = 12.0;
batt_lip = 1.4;
bay_gap = 5.0;           // battery ↔ MCU (wires)
mcu_xy_gap = 6.0;        // MCU ↔ XY-017 (UART Dupont)
wall_board_gap = 4.0;    // floor boards ↔ MT3608 wall pocket

/* [Pot rim clip — entirely outside sealed volume] */
clip_gap_min = 15.0;
clip_gap_max = 25.0;
clip_hook_t = 2.8;
clip_width = 30.0;
clip_strap_h = 50.0;

/* [Zip-tie slots on clip plate (not through cavity)] */
ziptie_w = 20.0;
ziptie_h = 3.0;
ziptie_count = 2;
ziptie_spacing = 18.0;
ziptie_z0 = 14.0;

/* [Preview] */
show_components = true;
show_usb_cap = true;

$fn = 64;
eps = 0.08;

// BOM footprints (L×W×H) — see docs/setup/HARDWARE_BOM.md
batt_l = 78.0; batt_w = 21.0; batt_h = 20.0;   // 18650 holder (vertical: L along Z)
mcu_l  = 48.0; mcu_w  = 25.0; mcu_h  = 13.0;   // NodeMCU
rs_l   = 43.0; rs_w   = 15.0; rs_h   = 12.0;   // XY-017
chg_l  = 26.0; chg_w  = 17.0; chg_h  = 4.5;    // TP4056 (L = USB axis)
bst_l  = 36.0; bst_w  = 17.0; bst_h  = 14.0;   // MT3608 (H incl. pot into cavity)

// Sealed outer shell (cavity + walls)
sealed_w = inner_w + 2 * wall;
sealed_d = inner_d + 2 * wall;
sealed_h = inner_h + wall;

outer_w = sealed_w + 2 * flange;
outer_d = sealed_d + 2 * flange;
outer_h = sealed_h;

sealed_x0 = flange;
sealed_y0 = flange;

ix0 = sealed_x0 + wall;
iy0 = sealed_y0 + wall;
iz0 = wall;

// ----- Layout anchors (cavity-relative packing) -----
// Floor band along −Y; +Y reserved for MT3608 wall pocket + gland/service
batt_x = ix0 + 2.0;
batt_y = iy0 + 2.0;

// NodeMCU: long axis along +X (fits width), short along +Y
mcu_x = batt_x + batt_w + bay_gap;
mcu_y = iy0 + 2.0;

// XY-017: same −Y band, +Y of MCU (not stacked above it)
rs_x = mcu_x;
rs_y = mcu_y + mcu_w + mcu_xy_gap;

// TP4056 short-edge USB: length along X into cavity, elevated Z
chg_x = ix0 + inner_w - chg_l;
chg_y = iy0 + (inner_d - chg_w) / 2;
chg_z = usb_c_z - chg_h / 2;

// MT3608 on +Y wall: PCB parallel to wall, components point −Y into cavity
bst_x = ix0 + (inner_w - bst_l) / 2;
bst_y = iy0 + inner_d - bst_h - mount_clear;
bst_z = iz0 + 28.0;

// Gland in +Y service strip (clear of floor boards)
gland_x = ix0 + inner_w / 2;
gland_y = iy0 + inner_d - 11.0;

function screw_xy() = [
  [ flange_screw_inset,                 flange_screw_inset ],
  [ outer_w - flange_screw_inset,       flange_screw_inset ],
  [ flange_screw_inset,                 outer_d - flange_screw_inset ],
  [ outer_w - flange_screw_inset,       outer_d - flange_screw_inset ]
];

function usb_hole_origin() = [
  sealed_x0 + sealed_w - wall - eps,
  sealed_y0 + (sealed_d - usb_c_w) / 2,
  usb_c_z - usb_c_h / 2
];

function usb_pad_w() = usb_c_w + 2 * usb_seal_pad;
function usb_pad_h() = usb_c_h + 2 * usb_seal_pad;

module rounded_box(size, r) {
  w = size[0]; d = size[1]; h = size[2];
  rr = min(r, min(w, d) / 2 - 0.05);
  hull() for (x = [rr, w - rr], y = [rr, d - rr])
    translate([x, y, 0]) cylinder(h = h, r = rr);
}

// ---------------------------------------------------------------------------
// Snap-fit board bay: floor pad + side rails with retaining lips.
// Board lies in XY; thickness along Z. Open top = insert from above.
module board_clip_bay(bw, bd, bh, z0 = 0) {
  c = mount_clear;
  rt = clip_rail_t;
  lip = clip_lip;
  pad = 1.2;
  rail_h = max(bh + 1.5, 5.0);

  translate([0, 0, z0]) {
    translate([-0.5, -0.5, 0])
      cube([bw + 1.0, bd + 1.0, pad]);

    for (side = [0, 1]) {
      x = side == 0 ? -rt : bw + c;
      translate([x, -0.3, 0]) {
        cube([rt, bd + 0.6 + c, rail_h]);
        translate([side == 0 ? rt - 0.05 : -lip + 0.05, 0, rail_h - 1.4])
          cube([lip, bd + 0.6 + c, 1.4]);
      }
    }

    stop_h = min(rail_h * 0.55, bh + 0.8);
    translate([-0.2, -rt, 0])
      cube([bw + c + 0.4, rt, stop_h]);
    translate([-0.2, bd + c, 0])
      cube([bw + c + 0.4, rt, stop_h]);
  }
}

// Elevated shelf for TP4056: short-edge USB faces +X through wall.
// Board: L along X, W along Y, thickness along Z. Open toward −X for slide-in.
module tp4056_short_edge_shelf() {
  c = mount_clear;
  rt = clip_rail_t;
  lip = clip_lip;
  // Side rails under/along Y edges; rear stop near wall; open mouth toward cavity
  translate([chg_x, chg_y, chg_z]) {
    // Floor pad under PCB
    translate([-0.5, -0.5, 0])
      cube([chg_l + 0.5, chg_w + 1.0, 1.2]);

    // ±Y rails with top lips (retain board)
    for (side = [0, 1]) {
      y = side == 0 ? -rt : chg_w + c;
      translate([0, y, 0]) {
        cube([chg_l * 0.85, rt, chg_h + 2.0]);
        translate([0, side == 0 ? rt - 0.05 : -lip + 0.05, chg_h + 0.6])
          cube([chg_l * 0.75, lip, 1.3]);
      }
    }

    // Wall-end stop (near +X) — leaves USB nose free toward wall hole
    translate([chg_l - 2.2, -0.3, 0])
      cube([2.2, chg_w + c + 0.6, chg_h + 1.0]);
  }
}

// MT3608 pocket on +Y wall: board in XZ, components point −Y into cavity.
// Origin at −X/−Y/bottom corner of component envelope (bst_l × bst_h × bst_w).
module mt3608_plusy_pocket() {
  c = mount_clear;
  rt = clip_rail_t;
  lip = clip_lip;
  // Envelope: X=length 36, Y=thickness into cavity 14, Z=width 17
  translate([bst_x, bst_y, bst_z]) {
    translate([-0.4, -0.2, 0])
      cube([bst_l + 0.8, bst_h + c + 0.4, 1.2]);

    for (side = [0, 1]) {
      x = side == 0 ? -rt : bst_l + c;
      translate([x, -0.2, 0]) {
        cube([rt, bst_h + c + 0.4, bst_w * 0.9]);
        translate([side == 0 ? rt - 0.05 : -lip + 0.05, -lip + 0.1, bst_w * 0.45])
          cube([lip, lip + 0.2, 2.2]);
      }
    }

    translate([-0.2, -0.2, 0])
      cube([bst_l + c + 0.4, bst_h + c + 0.4, 1.6]);
  }
}

module usb_c_cutouts() {
  o = usb_hole_origin();
  translate(o)
    cube([wall + 2 * eps, usb_c_w, usb_c_h]);
  translate([
    sealed_x0 + sealed_w - usb_recess_depth,
    sealed_y0 + (sealed_d - usb_pad_w()) / 2,
    usb_c_z - usb_pad_h() / 2
  ])
    cube([usb_recess_depth + eps, usb_pad_w(), usb_pad_h()]);
}

module usb_cap() {
  plug_w = usb_c_w - usb_fit_clear;
  plug_h = usb_c_h - usb_fit_clear;
  flange_w = usb_pad_w() - 0.4;
  flange_h = usb_pad_h() - 0.4;
  plug_len = wall + usb_cap_plug_extra;

  union() {
    translate([0, -flange_w / 2, -flange_h / 2])
      cube([usb_recess_depth + 0.4, flange_w, flange_h]);
    translate([usb_recess_depth - 0.1, -plug_w / 2, -plug_h / 2])
      cube([plug_len, plug_w, plug_h]);
    translate([-usb_cap_tab, -3.0, -1.2])
      cube([usb_cap_tab + 0.2, 6.0, 2.4]);
  }
}

module body() {
  difference() {
    union() {
      translate([sealed_x0, sealed_y0, 0])
        rounded_box([sealed_w, sealed_d, sealed_h], corner_r);

      translate([0, 0, sealed_h - boss_h])
        difference() {
          rounded_box([outer_w, outer_d, boss_h], corner_r + 1);
          translate([sealed_x0 + 0.01, sealed_y0 + 0.01, -eps])
            rounded_box([sealed_w - 0.02, sealed_d - 0.02, boss_h + 2 * eps], corner_r);
        }

      pot_clip();

      translate([gland_x, gland_y, -gland_boss_h])
        cylinder(h = gland_boss_h + eps, d = gland_boss_od);
    }

    translate([sealed_x0 + wall, sealed_y0 + wall, wall])
      rounded_box([inner_w, inner_d, inner_h + 2], max(0.8, corner_r - 0.8));

    translate([0, 0, sealed_h - gasket_h])
      seal_groove();

    for (p = screw_xy())
      translate([p[0], p[1], sealed_h - insert_depth])
        cylinder(h = insert_depth + 1, d = insert_od);

    translate([gland_x, gland_y, -gland_boss_h - 1])
      cylinder(h = wall + gland_boss_h + 2, d = gland_hole_d);

    if (usb_c_port)
      usb_c_cutouts();
  }

  for (p = screw_xy())
    translate([p[0], p[1], sealed_h - boss_h])
      difference() {
        cylinder(h = boss_h, d = boss_od);
        translate([0, 0, boss_h - insert_depth])
          cylinder(h = insert_depth + 1, d = insert_od);
      }

  electronics_mounts();
}

module seal_groove() {
  ox = sealed_x0 + wall / 2 - gasket_w / 2 - groove_play;
  oy = sealed_y0 + wall / 2 - gasket_w / 2 - groove_play;
  ow = sealed_w - wall + gasket_w + 2 * groove_play;
  od = sealed_d - wall + gasket_w + 2 * groove_play;
  difference() {
    translate([ox, oy, 0])
      rounded_box([ow, od, gasket_h + eps], corner_r);
    translate([ox + gasket_w, oy + gasket_w, -eps])
      rounded_box(
        [ow - 2 * gasket_w, od - 2 * gasket_w, gasket_h + 2 * eps],
        max(0.5, corner_r - 1)
      );
  }
}

module battery_cradle() {
  x0 = batt_x;
  y0 = batt_y;
  zh = batt_rail_h;
  rt = 2.2;

  translate([x0 - 0.5, y0 - 0.5, iz0])
    cube([batt_w + 1.0, batt_h + 1.0, 1.4]);

  translate([x0 - rt, y0 - rt, iz0])
    cube([rt, batt_h + 2 * rt, zh]);
  translate([x0 + batt_w, y0 - rt, iz0])
    cube([rt, batt_h + 2 * rt, zh]);
  translate([x0 - rt, y0 - rt, iz0])
    cube([batt_w + 2 * rt, rt, zh]);
  translate([x0 - rt, y0 + batt_h, iz0])
    cube([batt_w + 2 * rt, rt, zh]);

  translate([x0 - rt, y0 - rt, iz0 + zh - 1.6])
    cube([rt + batt_lip, batt_h + 2 * rt, 1.6]);
  translate([x0 + batt_w - batt_lip, y0 - rt, iz0 + zh - 1.6])
    cube([rt + batt_lip, batt_h + 2 * rt, 1.6]);

  for (z = [iz0 + 28, iz0 + 55]) {
    translate([x0 - rt, y0 + batt_h / 2 - 3, z])
      cube([rt + 1.0, 6, 2.2]);
    translate([x0 + batt_w - 1.0, y0 + batt_h / 2 - 3, z])
      cube([rt + 1.0, 6, 2.2]);
  }
}

module electronics_mounts() {
  battery_cradle();

  // Console checks when rendering
  if (mcu_x + mcu_l > ix0 + inner_w - 1)
    echo("WARNING V1.5: NodeMCU exceeds inner_w");
  if (rs_y + rs_w + wall_board_gap > bst_y)
    echo("WARNING V1.5: XY-017 collides with MT3608 wall zone");
  if (chg_z < iz0 + mcu_h + 8)
    echo("WARNING V1.5: TP4056 shelf too low over floor boards");

  // Floor bays — open above for drop-in (MCU long along X)
  translate([mcu_x, mcu_y, iz0])
    board_clip_bay(mcu_l, mcu_w, mcu_h, 0);

  translate([rs_x, rs_y, iz0])
    board_clip_bay(rs_l, rs_w, rs_h, 0);

  mt3608_plusy_pocket();
  tp4056_short_edge_shelf();
}

module pot_clip() {
  cw = clip_width;
  t = clip_hook_t;
  x0 = sealed_x0 + (sealed_w - cw) / 2;
  y0 = sealed_y0 - t - clip_gap_max;
  difference() {
    translate([x0, y0, 10]) {
      cube([cw, t, clip_strap_h]);
      translate([0, 0, clip_strap_h - t])
        cube([cw, t + clip_gap_max + t, t]);
      hull() {
        translate([0, t + clip_gap_min, clip_strap_h - t - 8])
          cube([cw, t, 8]);
        translate([0, t + clip_gap_max - 0.5, 10])
          cube([cw, t, 2]);
      }
    }
    for (i = [0 : ziptie_count - 1]) {
      translate([
        x0 + (cw - ziptie_w) / 2,
        y0 - eps,
        10 + ziptie_z0 + i * ziptie_spacing
      ])
        cube([ziptie_w, t + 2 * eps, ziptie_h]);
    }
  }
}

module lid() {
  lid_z = wall + 0.8;
  difference() {
    union() {
      rounded_box([outer_w, outer_d, lid_z], corner_r + 1);

      ox = sealed_x0 + wall / 2 - tongue_w / 2;
      oy = sealed_y0 + wall / 2 - tongue_w / 2;
      ow = sealed_w - wall + tongue_w;
      od = sealed_d - wall + tongue_w;
      translate([ox, oy, -tongue_h])
        difference() {
          rounded_box([ow, od, tongue_h], corner_r);
          translate([tongue_w, tongue_w, -eps])
            rounded_box(
              [ow - 2 * tongue_w, od - 2 * tongue_w, tongue_h + 2 * eps],
              max(0.4, corner_r - 1)
            );
        }

      lip = 0.8;
      translate([sealed_x0 - lip, sealed_y0 - lip, -0.6])
        difference() {
          rounded_box([sealed_w + 2 * lip, sealed_d + 2 * lip, 0.6], corner_r);
          translate([lip + 0.2, lip + 0.2, -eps])
            rounded_box(
              [sealed_w - 0.4, sealed_d - 0.4, 0.6 + 2 * eps],
              max(0.5, corner_r - 0.2)
            );
        }
    }

    for (p = screw_xy()) {
      translate([p[0], p[1], -tongue_h - 1])
        cylinder(h = lid_z + tongue_h + 2, d = m3_clear);
      translate([p[0], p[1], lid_z - m3_csink_h + eps])
        cylinder(h = m3_csink_h, d1 = m3_clear, d2 = m3_csink_d);
    }
  }
}

module gasket() {
  ox = sealed_x0 + wall / 2 - gasket_w / 2;
  oy = sealed_y0 + wall / 2 - gasket_w / 2;
  ow = sealed_w - wall + gasket_w;
  od = sealed_d - wall + gasket_w;
  difference() {
    translate([ox, oy, 0])
      rounded_box([ow, od, gasket_h], corner_r - 0.2);
    translate([ox + gasket_w, oy + gasket_w, -eps])
      rounded_box(
        [ow - 2 * gasket_w, od - 2 * gasket_w, gasket_h + 2 * eps],
        max(0.4, corner_r - 1)
      );
  }
}

module component_ghosts() {
  if (show_components) {
    color("SteelBlue", 0.45)
      translate([batt_x, batt_y, iz0 + 1.4])
        cube([batt_w, batt_h, batt_l]);

    // NodeMCU — long along X
    color("ForestGreen", 0.45)
      translate([mcu_x, mcu_y, iz0 + 1.2])
        cube([mcu_l, mcu_w, mcu_h]);

    // XY-017 — floor beside MCU (+Y), not stacked
    color("DarkOrange", 0.45)
      translate([rs_x, rs_y, iz0 + 1.2])
        cube([rs_l, rs_w, rs_h]);

    // TP4056 — short-edge USB toward +X (L along X)
    color("Crimson", 0.5)
      translate([chg_x, chg_y, chg_z])
        cube([chg_l, chg_w, chg_h]);

    // MT3608 — on +Y wall (components into cavity)
    color("Gold", 0.45)
      translate([bst_x, bst_y, bst_z])
        cube([bst_l, bst_h, bst_w]);
  }
}

module preview_assembly() {
  color(body_color, 0.92)
    body();
  color("Black", 0.6)
    translate([0, 0, sealed_h - gasket_h]) gasket();
  color(lid_color, 0.9)
    translate([0, 0, sealed_h + 0.35]) lid();
  if (usb_c_port && show_usb_cap) {
    color([0.12, 0.12, 0.12], 0.9)
      translate([
        sealed_x0 + sealed_w - usb_recess_depth,
        sealed_y0 + sealed_d / 2,
        usb_c_z
      ])
        usb_cap();
  }
  component_ghosts();
}

if (part == "body") body();
else if (part == "lid") lid();
else if (part == "gasket") gasket();
else if (part == "usb_cap") usb_cap();
else preview_assembly();
