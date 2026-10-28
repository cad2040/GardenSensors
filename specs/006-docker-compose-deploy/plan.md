# Implementation Plan: 006-docker-compose-deploy

**Branch**: `006-docker-compose-deploy` | **Date**: retrospective | **Spec**: [spec.md](./spec.md)  
**Status**: Delivered

## Summary

Add a first-class Docker Compose deploy path with health-gated tests, container seed parity, and docs, while hardening plot/Python resolution and CRUD endpoints for both bare-metal and Compose.

## Technical Context

**Language/Version**: Docker Compose, PHP-FPM/Apache image, Python image, MySQL  
**Primary Dependencies**: `docker-compose.yml`, `docker/Dockerfile.php`, `docker/Dockerfile.python`  
**Storage**: MySQL service + `docker/mysql/` seed scripts  
**Testing**: Health-gated test run via setup docker mode  
**Target Platform**: Linux Docker hosts (x86_64 primary at delivery)  
**Constraints**: Keep `setup.sh` as the umbrella entry; avoid parallel unrelated installers

## Project Structure

```text
docker-compose.yml
docker/Dockerfile.php
docker/Dockerfile.python
docker/mysql/
.env.docker.example
docs/setup/docker.md
setup.sh          # docker mode hooks
cleanup.sh
```

**Structure Decision**: Compose files at repo root; image build contexts under `docker/`.
