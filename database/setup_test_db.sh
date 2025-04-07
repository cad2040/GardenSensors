#!/bin/bash

# Get the directory where this script is located
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

# Database configuration
DB_HOST="localhost"
DB_NAME="garden_sensors_test"
DB_USER="root"
DB_PASS="garden_sensors"

# Create database and tables
mysql -u"$DB_USER" -p"$DB_PASS" << EOF
CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE $DB_NAME;
source $SCRIPT_DIR/schema.sql;
source $SCRIPT_DIR/migrations/001_fix_test_tables.sql;
EOF

# Create test user with necessary permissions
mysql -u"$DB_USER" -p"$DB_PASS" << EOF
CREATE USER IF NOT EXISTS 'garden_user'@'localhost' IDENTIFIED BY 'CHANGE_THIS_PASSWORD';

GRANT ALL PRIVILEGES ON $DB_NAME.* TO 'garden_user'@'localhost';
FLUSH PRIVILEGES;
EOF

echo "Test database setup completed." 