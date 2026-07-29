# GardenSensors agent chat themes

Derived from the linked **gardensensors** chat list in Cursor (titles and
timestamps), plus inspection of the current repo. Prefer this file for
historical pitfalls; prefer `SESSION_SUMMARY.md` and `docs/` for current state.

## Chat sessions (by recency)

| Approx. age | Title (as shown) | Likely focus |
|-------------|------------------|--------------|
| recent | Why won't my repo load? | WSL remote vs Windows clone, folder access |
| 2mo | Chat version support error | Cursor/model compatibility reopening old chats |
| 8mo | Deploy locally and add t… | Local stack + tooling |
| 8mo | Continue from session s… | Resume prior agent work |
| 9mo | Deploy project locally fo… | End-to-end local run |
| 11mo | Setup project and run u… | First-time setup + run |
| 1y | Local Deployment for U… | Local deployment variant |
| 1y | Project Setup and Test E… | Setup + test environment |
| 1y | Removing Local Deploy… | Tear down local deploy approach |
| 1y | Local Deployment Clean… | Cleanup after experiments |
| 1y | Executing PHP Unit Tests | PHPUnit and test/code alignment |
| 1y | Local Application Deploy… | App local serving |
| 1y | Script Review and Updat… | Shell/deploy/Python script maintenance |
| 1y | File Reorganization Plan … | Repo structure planning |
| 1y | Purge Local Deployment… | Remove failed/redundant deploy files |
| 1y | Local Application Deploy… | Repeat local deploy iteration |
| 1y | Improving Repository Str… | Structure and conventions |
| 1y | Project Setup and Testin… | Setup + testing |
| 1y | Installing Requirements … | Composer, pip, system deps |

## Recurring patterns (steer away from repeats)

1. **Local deployment churn** — Many chats created, cleaned, purged, or removed
   local deploy setups. Use `setup.sh` / `cleanup.sh` only.
2. **Setup loops** — Verify what is already installed before reinstalling.
3. **Structure follow-through** — Domain code belongs in `src/`; web entry in
   `public/`. Do not invent a second parallel layout (e.g. revived `GUI/`-only
   trees on stale clones).
4. **Tests as a gate** — Deploy expects PHP + Python suites to pass.
5. **Environment friction** — "Repo won't load" often means WSL remote vs a
   stale Windows clone. Sync via git pull on the outdated side.

## Current repo facts (for agents)

- Autoload: `GardenSensors\` → `src/`
- Web root content: `public/` (not a top-level `GUI/` on current master)
- Canonical schema area: `database/`
- Root `SQLDeployScript.sql` may still exist for legacy reference; prefer
  `database/schema.sql` + migrations for new work
- Plants link to sensors via `plant_sensors` (do not bring back `fact_plants`)
- Plot/summary APIs drive dashboard cards and Bokeh plots

## Suggested next milestones

1. Keep Windows and WSL clones on the same commit after pulls
2. Keep `SESSION_SUMMARY.md` updated after meaningful sessions
3. Trim outdated paths in older docs under `docs/setup` when touched
4. Avoid documenting real credentials; point at `.env.example` only
