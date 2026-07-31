// GardenSensors — Outdoor plant-pot IoT node enclosure (V1.1 — weatherproof seal)
// OpenSCAD parametric model for PETG / ASA (+ TPU gasket)
//
// Seal strategy (IP-minded):
//   • Continuous gasket groove on the sealed rim
//   • Lid tongue compresses the gasket
//   • M3 screws sit on an OUTER flange — they never pierce the wet→dry path
//   • No USB sidewall hole by default (charge indoors with lid open)
//   • Zip-tie slots are on the EXTERNAL clip, not through the cavity wall
//   • Bottom M12 gland + thread sealant is the only cable penetration
//
// Units: mm
// part = "body" | "lid" | "gasket" | "preview"
//
// Docs: docs/setup/HARDWARE_BOM.md

/* [Which part] */
part = "preview"; // ["body", "lid", "gasket", "preview"]

/* [Sealed internal cavity] */
inner_w = 54.0;
inner_d = 44.0;
inner_h = 90.0;

/* [Shell] */
wall = 2.4;
corner_r = 3.0;
flange = 9.0;          // outer clamp flange (screws live here)

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
// Screw centres: midway across flange, clear of seal
flange_screw_inset = flange / 2;

/* [M12×1.5 IP68 gland — bottom only] */
gland_hole_d = 12.0;
gland_boss_od = 18.0;
gland_boss_h = 3.5;

/* [USB-C sidewall — OFF for weatherproof PoC; charge with lid open] */
usb_c_port = false;
usb_c_w = 9.2;
usb_c_h = 3.6;
usb_c_z = 18.0;

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

$fn = 64;
eps = 0.08;

batt_l = 78.0; batt_w = 21.0; batt_h = 20.0;
mcu_l  = 48.0; mcu_w  = 25.0; mcu_h  = 13.0;
rs_l   = 43.0; rs_w   = 15.0; rs_h   = 12.0;
chg_l  = 26.0; chg_w  = 17.0; chg_h  = 4.5;
bst_l  = 36.0; bst_w  = 17.0; bst_h  = 14.0;

// Sealed outer shell (cavity + walls)
sealed_w = inner_w + 2 * wall;
sealed_d = inner_d + 2 * wall;
sealed_h = inner_h + wall;          // floor + walls; open top

// Overall body including clamp flange
outer_w = sealed_w + 2 * flange;
outer_d = sealed_d + 2 * flange;
outer_h = sealed_h;

// Origin: outer flange corner at (0,0). Sealed box inset by `flange`.
sealed_x0 = flange;
sealed_y0 = flange;

function screw_xy() = [
  [ flange_screw_inset,                 flange_screw_inset ],
  [ outer_w - flange_screw_inset,       flange_screw_inset ],
  [ flange_screw_inset,                 outer_d - flange_screw_inset ],
  [ outer_w - flange_screw_inset,       outer_d - flange_screw_inset ]
];

module rounded_box(size, r) {
  w = size[0]; d = size[1]; h = size[2];
  rr = min(r, min(w, d) / 2 - 0.05);
  hull() for (x = [rr, w - rr], y = [rr, d - rr])
    translate([x, y, 0]) cylinder(h = h, r = rr);
}

// ---------------------------------------------------------------------------
module body() {
  difference() {
    union() {
      // Outer flange slab (full height would be heavy — use rim flange + sealed box)
      // Sealed box
      translate([sealed_x0, sealed_y0, 0])
        rounded_box([sealed_w, sealed_d, sealed_h], corner_r);

      // Top clamp flange (ring around sealed box)
      translate([0, 0, sealed_h - boss_h])
        difference() {
          rounded_box([outer_w, outer_d, boss_h], corner_r + 1);
          translate([sealed_x0 + 0.01, sealed_y0 + 0.01, -eps])
            rounded_box([sealed_w - 0.02, sealed_d - 0.02, boss_h + 2 * eps], corner_r);
        }

      pot_clip();

      // Gland boss under sealed floor
      translate([sealed_x0 + sealed_w / 2, sealed_y0 + sealed_d / 2, -gland_boss_h])
        cylinder(h = gland_boss_h + eps, d = gland_boss_od);
    }

    // Sealed cavity
    translate([sealed_x0 + wall, sealed_y0 + wall, wall])
      rounded_box([inner_w, inner_d, inner_h + 2], max(0.8, corner_r - 0.8));

    // Gasket groove in TOP of sealed rim (continuous — no screw interruptions)
    translate([0, 0, sealed_h - gasket_h])
      seal_groove();

    // Insert bores in flange (outside seal)
    for (p = screw_xy())
      translate([p[0], p[1], sealed_h - insert_depth])
        cylinder(h = insert_depth + 1, d = insert_od);

    // Gland through sealed floor only
    translate([sealed_x0 + sealed_w / 2, sealed_y0 + sealed_d / 2, -gland_boss_h - 1])
      cylinder(h = wall + gland_boss_h + 2, d = gland_hole_d);

    // Optional USB (breaks weather seal — off by default)
    if (usb_c_port)
      translate([
        sealed_x0 + sealed_w - wall - eps,
        sealed_y0 + (sealed_d - usb_c_w) / 2,
        usb_c_z - usb_c_h / 2
      ])
        cube([wall + 2 * eps, usb_c_w, usb_c_h]);
  }

  // Flange insert bosses (solid cylinders with bore)
  for (p = screw_xy())
    translate([p[0], p[1], sealed_h - boss_h])
      difference() {
        cylinder(h = boss_h, d = boss_od);
        translate([0, 0, boss_h - insert_depth])
          cylinder(h = insert_depth + 1, d = insert_od);
      }

  battery_cradle();
  pcb_shelf();
}

module seal_groove() {
  // Groove centred on sealed wall thickness
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
  rail = 2.0;
  zh = 3.0;
  x0 = sealed_x0 + wall + 2;
  y0 = sealed_y0 + wall + (inner_d - batt_h) / 2;
  translate([x0 - rail, y0 - rail, wall]) cube([rail, batt_h + 2 * rail, zh]);
  translate([x0 + batt_w, y0 - rail, wall]) cube([rail, batt_h + 2 * rail, zh]);
  translate([x0, y0 - rail, wall]) cube([batt_w, rail, zh]);
  translate([x0, y0 + batt_h, wall]) cube([batt_w, rail, zh]);
}

module pcb_shelf() {
  shelf_z = 2.0;
  x0 = sealed_x0 + wall + batt_w + 6;
  translate([x0, sealed_y0 + wall + 2, wall])
    cube([inner_w - (x0 - sealed_x0 - wall) - 2, inner_d - 4, shelf_z]);
}

module pot_clip() {
  // Mounted on sealed rear face — entirely outside cavity
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
    // Zip-tie slots through outer clip strap only (not cavity)
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
  lid_z = wall + 0.8; // slightly thicker for stiffness over flange
  difference() {
    union() {
      // Full flange cover
      rounded_box([outer_w, outer_d, lid_z], corner_r + 1);

      // Compressing tongue into gasket groove
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

      // Optional drip lip outside tongue (sheds water before gasket)
      lip = 0.8;
      translate([
        sealed_x0 - lip,
        sealed_y0 - lip,
        -0.6
      ])
        difference() {
          rounded_box([sealed_w + 2 * lip, sealed_d + 2 * lip, 0.6], corner_r);
          translate([lip + 0.2, lip + 0.2, -eps])
            rounded_box(
              [sealed_w - 0.4, sealed_d - 0.4, 0.6 + 2 * eps],
              max(0.5, corner_r - 0.2)
            );
        }
    }

    // Screw holes ONLY on flange (outside sealed perimeter)
    for (p = screw_xy()) {
      translate([p[0], p[1], -tongue_h - 1])
        cylinder(h = lid_z + tongue_h + 2, d = m3_clear);
      translate([p[0], p[1], lid_z - m3_csink_h + eps])
        cylinder(h = m3_csink_h, d1 = m3_clear, d2 = m3_csink_d);
    }
  }
}

module gasket() {
  // Matches seal_groove plan
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
    color("SteelBlue", 0.4)
      translate([sealed_x0 + wall + 2, sealed_y0 + wall + (inner_d - batt_h) / 2, wall])
        cube([batt_w, batt_h, batt_l]);
    color("ForestGreen", 0.4)
      translate([sealed_x0 + wall + batt_w + 5, sealed_y0 + wall + 4, wall + 8])
        cube([mcu_w, mcu_h, mcu_l]);
    color("DarkOrange", 0.4)
      translate([sealed_x0 + wall + batt_w + 5, sealed_y0 + wall + 20, wall + 8])
        cube([rs_w, rs_h, rs_l]);
    color("Crimson", 0.45)
      translate([
        sealed_x0 + wall + batt_w + 8,
        sealed_y0 + wall + inner_d - chg_w - 4,
        wall + 6
      ])
        cube([chg_h + 2, chg_w, chg_l]);
    color("Gold", 0.4)
      translate([sealed_x0 + wall + batt_w + 8, sealed_y0 + wall + 22, wall + 55])
        cube([bst_w, bst_h, bst_l]);
  }
}

module preview_assembly() {
  body();
  color("Black", 0.6)
    translate([0, 0, sealed_h - gasket_h]) gasket();
  color("LightGray", 0.88)
    translate([0, 0, sealed_h + 0.35]) lid();
  component_ghosts();
}

if (part == "body") body();
else if (part == "lid") lid();
else if (part == "gasket") gasket();
else preview_assembly();
