# Implementation Plan: 004-deploy-and-test-harness

**Branch**: `004-deploy-and-test-harness` | **Date**: retrospective | **Spec**: [spec.md](./spec.md)  
**Status**: Delivered

## Summary

Make GardenSensors installable and verifiable via canonical `setup.sh` / `cleanup.sh`, with PHPUnit + Python tests as a deploy gate, after cleaning redundant directory trees into `public/` + `src/` + `database/`.

## Technical Context

**Language/Version**: Bash, PHP, Python 3  
**Primary Dependencies**: Composer, pip/venv, Apache, MySQL, PHPUnit  
**Storage**: MySQL schemas via `database/schema.sql` + migrations  
**Testing**: `composer test` / `vendor/bin/phpunit`, `tests/run_tests.sh`, Python tests under `tests/python/`  
**Target Platform**: Ubuntu 20.04+  
**Constraints**: Prefer extending these scripts over new one-off deploy trees

## Project Structure

```text
setup.sh
cleanup.sh
database/schema.sql
database/migrations/
database/setup_db.sh
tests/
composer.json
phpunit.xml
docs/setup/
docs/development/TESTING.md
docs/development/deployment.md
```

**Structure Decision**: Single deploy entrypoint at repo root; docs describe modes rather than alternate installers.
