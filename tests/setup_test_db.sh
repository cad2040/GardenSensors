#!/bin/bash

# Read database credentials from environment variables or use defaults
DB_HOST=${DB_HOST:-localhost}
DB_USER=${DB_USER:-root}
DB_PASS=${DB_PASS:-}

# Drop and recreate test database
mysql -h "$DB_HOST" -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} -e "DROP DATABASE IF EXISTS garden_sensors_test;"
mysql -h "$DB_HOST" -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} -e "CREATE DATABASE garden_sensors_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Drop and recreate test user
mysql -h "$DB_HOST" -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} -e "DROP USER IF EXISTS 'garden_test_user'@'localhost';"
mysql -h "$DB_HOST" -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} -e "CREATE USER 'garden_test_user'@'localhost' IDENTIFIED BY 'test_password';"
mysql -h "$DB_HOST" -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} -e "GRANT ALL PRIVILEGES ON garden_sensors_test.* TO 'garden_test_user'@'localhost';"
mysql -h "$DB_HOST" -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} -e "FLUSH PRIVILEGES;"

# Create test database and tables
mysql -h "$DB_HOST" -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} garden_sensors_test < tests/database.sql

echo "Test database setup complete!" 