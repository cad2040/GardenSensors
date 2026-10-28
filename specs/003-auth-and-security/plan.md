# Implementation Plan: 003-auth-and-security

**Branch**: `003-auth-and-security` | **Date**: retrospective | **Spec**: [spec.md](./spec.md)  
**Status**: Delivered

## Summary

Add authentication, environment-based configuration, and API rate limiting so the web UI can be safely deployed beyond a trusted single user.

## Technical Context

**Language/Version**: PHP  
**Primary Dependencies**: Sessions, `AuthService` / `AuthController`, `RateLimiterService`  
**Storage**: MySQL users table; `.env` for secrets  
**Testing**: PHPUnit coverage for services where present  
**Target Platform**: Apache + PHP web root  
**Constraints**: Constitution — no secrets in git

## Project Structure

```text
.env.example
public/login.php, logout.php, auth.php
src/Services/AuthService.php
src/Services/RateLimiterService.php
src/Controllers/AuthController.php
src/Models/User.php
public/includes/rate_limiter.php
```

**Structure Decision**: Auth domain in `src/`; thin public entry scripts remain under `public/`.
