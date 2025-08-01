#!/bin/bash

# Load environment variables from .env.test
if [ -f .env.test ]; then
    export $(cat .env.test | grep -v '^#' | xargs)
fi

# Set testing environment variable
export TESTING=true

# Run the configuration check
php tests/check_test_config.php 