# Implementation Plan: 009-alerts-email-docker-installer

**Branch**: `009-alerts-email-docker-installer` | **Date**: retrospective | **Spec**: [spec.md](./spec.md)  
**Status**: Delivered

## Summary

Ship Priority A notifications (settings-driven moisture/temp/pH/EC alerts + SMTP mail) together with a thin Docker installer and first-run wizard. Same delivery also advanced enclosure USB-C mounts (documented under `010`).

## Technical Context

**Language/Version**: PHP, Bash  
**Primary Dependencies**: `MailService`, `Notification` / `NotificationService`, cron check script  
**Storage**: `database/migrations/006_notifications.sql`  
**Testing**: `MailServiceTest` and related service tests  
**Target Platform**: Bare-metal cron + Docker thin install hosts  
**Constraints**: No secrets in git; SMTP optional at runtime

## Project Structure

```text
src/Services/MailService.php
src/Services/Notification.php
src/Services/NotificationService.php
database/migrations/006_notifications.sql
public/cron/check_alerts.php
public/settings.php
public/setup/index.php
public/setup/first_run.php
scripts/docker-install.sh
docs/setup/DOCKER_INSTALLER.md
```

**Structure Decision**: Alert domain in `src/Services`; installer script under `scripts/`; wizard under `public/setup/`.
