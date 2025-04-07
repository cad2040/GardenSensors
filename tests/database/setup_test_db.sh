#!/bin/bash

# Get the directory of this script
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

# Load environment variables from .env.test
if [ -f "$DIR/../../.env.test" ]; then
    source "$DIR/../../.env.test"
fi

# Default values if not set in .env.test
DB_USER=${DB_USER:-root}
DB_PASS=${DB_PASS:-garden_sensors}
DB_HOST=${DB_HOST:-localhost}

# Run the SQL script
mysql -u"$DB_USER" -p"$DB_PASS" -h"$DB_HOST" < "$DIR/setup_test_db.sql"

echo "Test database setup complete." 