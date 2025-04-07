#!/bin/bash

# Database configuration
DB_HOST="localhost"
DB_NAME="garden_sensors"
DB_USER="root"

# Prompt for MySQL password
echo "Please enter MySQL root password:"
read -s DB_PASS
echo

# Create database and tables
mysql -u"$DB_USER" -p"$DB_PASS" << EOF
CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE $DB_NAME;
source schema.sql;
source migrations/001_fix_prod_tables.sql;
EOF

# Create database user with restricted privileges
mysql -u"$DB_USER" -p"$DB_PASS" << EOF
CREATE USER IF NOT EXISTS 'garden_user'@'localhost' IDENTIFIED BY 'CHANGE_THIS_PASSWORD';

-- Grant specific privileges
GRANT SELECT, INSERT, UPDATE ON $DB_NAME.readings TO 'garden_user'@'localhost';
GRANT SELECT ON $DB_NAME.sensors TO 'garden_user'@'localhost';
GRANT SELECT ON $DB_NAME.plants TO 'garden_user'@'localhost';
GRANT SELECT ON $DB_NAME.plant_sensors TO 'garden_user'@'localhost';
GRANT SELECT ON $DB_NAME.pins TO 'garden_user'@'localhost';
GRANT SELECT, INSERT ON $DB_NAME.system_log TO 'garden_user'@'localhost';
GRANT SELECT, INSERT, UPDATE ON $DB_NAME.settings TO 'garden_user'@'localhost';

FLUSH PRIVILEGES;
EOF

echo "Database setup completed." 