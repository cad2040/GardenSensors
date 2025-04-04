#!/bin/bash

# MySQL root credentials
MYSQL_ROOT_USER="root"
MYSQL_ROOT_PASS=""

# Application database credentials
DB_NAME="garden_sensors_test"
DB_USER="garden_sensors"
DB_PASS="garden_sensors"

# Function to execute MySQL commands
mysql_execute() {
    if [ -z "$MYSQL_ROOT_PASS" ]; then
        sudo mysql -u "$MYSQL_ROOT_USER" "$@"
    else
        sudo mysql -u "$MYSQL_ROOT_USER" -p"$MYSQL_ROOT_PASS" "$@"
    fi
}

echo "Setting up MySQL database and user..."

# Create database
mysql_execute -e "CREATE DATABASE IF NOT EXISTS $DB_NAME;"

# Create user and grant privileges
mysql_execute -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';"
mysql_execute -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';"
mysql_execute -e "FLUSH PRIVILEGES;"

# Create tables
mysql_execute "$DB_NAME" < tests/database.sql

echo "MySQL setup complete!"
echo "Database: $DB_NAME"
echo "User: $DB_USER"
echo "Password: $DB_PASS" 