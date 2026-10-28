# Feature Specification: Priority A Alerts/Email & Docker Thin Installer

**Feature Branch**: `009-alerts-email-docker-installer`  
**Created**: 2026-08 (retrospective)  
**Status**: Delivered  
**Input**: Backfilled from notifications/SMTP services, settings-driven alerts cron, and `scripts/docker-install.sh` first-run wizard.

## User Scenarios & Testing

### User Story 1 - Email when pots need attention (Priority: P1)

Stage-1 pots raise settings-driven notifications for moisture/temp/pH/EC conditions; email is sent via SMTP when configured.

**Why this priority**: Priority A monitoring outcome—operators learn about problems without watching the dashboard.

**Independent Test**: Configure SMTP + thresholds; run `check_alerts` cron with out-of-range seeded data; confirm notification path (mail may be mocked in tests).

**Acceptance Scenarios**:

1. **Given** alert thresholds and fresh readings out of range, **When** cron runs, **Then** a notification is recorded/sent per settings.
2. **Given** SMTP not configured, **When** alerts fire, **Then** system fails soft (log/skip) without crashing cron.

### User Story 2 - First-run Docker thin installer (Priority: P1)

A deployer runs `./scripts/docker-install.sh` and completes a first-run web wizard under `/setup/` to bootstrap the Compose-oriented install.

**Independent Test**: Run installer script on a supported host; hit setup wizard; confirm stack ready.

### User Story 3 - Notifications schema (Priority: P2)

Notifications are persisted (`006_notifications.sql`) for audit/UI hooks.

## Requirements

### Functional Requirements

- **FR-001**: System MUST evaluate moisture/temp/pH/EC alert rules from settings + readings.
- **FR-002**: System MUST send email through `MailService` when SMTP settings are present.
- **FR-003**: Cron entrypoints (`public/cron/check_alerts.php`) MUST run headlessly.
- **FR-004**: `scripts/docker-install.sh` MUST provide a thin first-run install path.
- **FR-005**: First-run UI under `public/setup/` MUST guide initial configuration.
- **FR-006**: Notifications table/migration MUST exist and be documented for redeploy.
- **FR-007**: Secrets remain in env; installer hardens handling where applicable.

## Success Criteria

- **SC-001**: Out-of-range stage-1 pot produces an alert action when cron runs.
- **SC-002**: New Docker user can bootstrap via thin installer + `/setup/` without hand-editing every file.

## Assumptions

- Full SMTP end-to-end in production may still need operator DNS/credentials (SESSION_SUMMARY follow-up).
- Auto-watering remains Priority B / out of scope.
