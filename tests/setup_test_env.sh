#!/bin/bash

# Exit on error
set -e

# Load environment variables from .env.test
if [ -f .env.test ]; then
    export $(cat .env.test | grep -v '^#' | xargs)
fi

# Check for required environment variables
if [ -z "$DB_HOST" ] || [ -z "$DB_USER" ] || [ -z "$DB_PASS" ]; then
    echo "Error: Required environment variables are not set"
    echo "Please ensure DB_HOST, DB_USER, and DB_PASS are set in .env.test"
    exit 1
fi

# Set testing environment variable
export TESTING=true

# Activate virtual environment
source venv/bin/activate

# Run PHP setup script
php tests/setup.php

# Run PHPUnit tests
./vendor/bin/phpunit

# Clean up test database
mysql -u "$DB_USER" -p"$DB_PASS" -e "DROP DATABASE IF EXISTS garden_sensors_test;" 