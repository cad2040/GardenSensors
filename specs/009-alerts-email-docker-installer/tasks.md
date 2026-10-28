# Tasks: 009-alerts-email-docker-installer

**Input**: `/specs/009-alerts-email-docker-installer/`  
**Status**: Delivered — all tasks complete (retrospective)

## Phase 1: Notifications & mail

- [x] T001 Add notifications migration + schema updates
- [x] T002 [US1] Implement `MailService` and notification services
- [x] T003 [US1] Wire settings-driven alert evaluation in cron
- [x] T004 [US1] Extend settings UI for alert/SMTP-related prefs
- [x] T005 Add mail service tests

## Phase 2: Docker thin installer

- [x] T006 [US2] Implement `scripts/docker-install.sh`
- [x] T007 [US2] Add `/setup/` first-run wizard pages
- [x] T008 Document installer in `docs/setup/DOCKER_INSTALLER.md`
- [x] T009 Hook setup.sh where needed for installer discovery

## Notes

Backfilled from commit `a5df8bd` (alerts/email + Docker thin installer portion).
