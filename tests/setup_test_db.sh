#!/bin/bash

# Read database credentials from environment variables or use defaults
DB_HOST=${DB_HOST:-localhost}
DB_USER=${DB_USER:-root}
DB_PASS=${DB_PASS:-}

# Create test database and tables
mysql -h "$DB_HOST" -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} < tests/database.sql

echo "Test database setup complete!" 