# Development Guide

## Development Environment Setup

### Prerequisites
- PHP 8.0 or higher
- Python 3.8 or higher
- MySQL 5.7 or higher
- Composer
- Git

### Local Development Setup
1. Clone the repository
2. Install PHP dependencies:
   ```bash
   composer install
   ```
3. Install Python dependencies:
   ```bash
   python -m pip install -r requirements.txt
   ```
4. Copy environment file:
   ```bash
   cp config/environment/.env.example config/environment/.env
   ```
5. Configure your environment variables in `.env`
6. Set up the database:
   ```bash
   php tools/setup-database.php
   ```

## Code Style
- PHP: PSR-12
- Python: PEP 8
- JavaScript: ESLint with Airbnb config

## Testing
- Run PHP tests:
  ```bash
  ./vendor/bin/phpunit
  ```
- Run Python tests:
  ```bash
  python -m pytest
  ```

## Git Workflow
1. Create a feature branch from `develop`
2. Make your changes
3. Write tests
4. Submit a pull request

## Deployment
See [Deployment Guide](deployment.md) for detailed instructions.

## Troubleshooting
Common issues and their solutions are documented in [Troubleshooting Guide](troubleshooting.md). 