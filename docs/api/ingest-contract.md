# HTTP Ingest Contract: Pot-Node V1.5

**Location**: `docs/api/ingest-contract.md`  
**Status**: Draft (T005)  
**Branch**: `011-pot-node-v15-bringup`  

## Overview

This document defines the HTTP ingest contract for the pot-node V1.5 firmware migration from direct MySQL to **HTTP-only** ingestion. The endpoint accepts sensor readings via POST, authenticates using LAN-trust methods (IP whitelist), and persists data to the garden stack MySQL (server-side only).

## Endpoint Definition

| Attribute | Value |
|-----------|-------|
| **Method** | `POST` |
| **Path** | `public/api/ingest.php` |
| **Content-Type** | `application/json` |
| **Accept** | `application/json` |
| **Authentication** | IP whitelist only (LAN-trust) |

## Authentication Policy

Per FR-006, the ingest endpoint must reject unauthenticated requests. Only IP whitelist is supported for V1.5:

1. **IP Whitelist**: Allow only LAN subnet(s) (e.g., `192.168.1.0/24`) to reach the endpoint. UFW or Nginx reverse proxy enforces this.
**Decision for V1.5**: Use IP whitelist only (simpler for PoC). Document the LAN subnet pattern here; no secrets committed.

## Request Schema

```json
{
  "sensor_id": 1,
  "moisture": 42.3,
  "temperature": 21.5,
  "ec": 1.2,
  "ph": 6.8
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `sensor_id` | unsigned int | Yes | Device identifier (e.g., 1) |
| `moisture` | float | Yes | Soil moisture percentage or raw ADC value |
| `temperature` | float | No | Temperature in °C (optional) |
| `ec` | float | No | Electrical conductivity in µS/cm (optional) |
| `ph` | float | No | pH level (optional) |

*Note: `field_notes` is not persisted / out of scope for T006.*

## Response Schema

### 200 OK — Reading Accepted

```json
{
  "status": "accepted",
  "reading_id": 1734892156,
  "timestamp": "2024-12-23T14:42:36Z"
}
```

### 400 Bad Request — Invalid Payload

```json
{
  "status": "error",
  "code": "INVALID_PAYLOAD",
  "message": "Missing required field: moisture"
}
```

### 401 Unauthorized — IP Not Whitelisted

```json
{
  "status": "error",
  "code": "AUTH_FAILED",
  "message": "IP address not in whitelist. Contact administrator."
}
```

### 403 Forbidden — Endpoint Disabled

```json
{
  "status": "error",
  "code": "ENDPOINT_DISABLED",
  "message": "Ingest endpoint is currently disabled for maintenance."
}
```

## Security Notes

- No secrets (API keys, passwords) are transmitted or stored in the request body.
- All readings are server-side only; MCUs never touch MySQL directly.
- IP whitelist is enforced at network layer (UFW/Nginx) and application layer.