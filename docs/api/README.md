# API Documentation

## Overview
This document describes the API endpoints available in the Garden Sensors system.

## Authentication
All API endpoints require authentication using a JWT token. Include the token in the Authorization header:
```
Authorization: Bearer <your_token>
```

## Endpoints

### Sensors

#### GET /api/sensors
Returns a list of all registered sensors.

#### GET /api/sensors/{id}
Returns detailed information about a specific sensor.

#### POST /api/sensors/reading
Submit a new sensor reading.

### Data

#### GET /api/data
Retrieve sensor data with optional filtering.

#### GET /api/data/statistics
Get statistical information about sensor readings.

## Error Codes
- 400: Bad Request
- 401: Unauthorized
- 403: Forbidden
- 404: Not Found
- 500: Internal Server Error

## Rate Limiting
API requests are limited to 100 requests per minute per IP address. 