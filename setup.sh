#!/bin/bash

# Garden Sensors Setup Script
# This script provides different setup options for the Garden Sensors project

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color
PYTHON_VENV_PATH=""

# Function to print status messages
print_status() {
    echo -e "${GREEN}[*] $1${NC}"
}

# Function to print error messages
print_error() {
    echo -e "${RED}[!] Error: $1${NC}"
    exit 1
}

# Function to print warning messages
print_warning() {
    echo -e "${YELLOW}[!] Warning: $1${NC}"
}

# Function to print info messages
print_info() {
    echo -e "${BLUE}[i] $1${NC}"
}

# Function to print step header
print_step() {
    echo -e "\n========================================"
    echo " STEP: $1 "
    echo "========================================\n"
}

# Function to detect Python virtual environment path
detect_python_venv() {
    if [ -n "$PYTHON_VENV" ] && [ -f "$PYTHON_VENV/bin/activate" ]; then
        PYTHON_VENV_PATH="$PYTHON_VENV"
        return 0
    fi

    if [ -f ".venv/bin/activate" ]; then
        PYTHON_VENV_PATH=".venv"
        return 0
    fi

    if [ -f "venv/bin/activate" ]; then
        PYTHON_VENV_PATH="venv"
        return 0
    fi

    return 1
}

# Function to show usage
show_usage() {
    echo "Usage: $0 [OPTION]"
    echo ""
    echo "Options:"
    echo "  production    Full production setup (requires root)"
    echo "  test          Test environment setup (requires root)"
    echo "  local         Local development setup (no root required)"
    echo "  help          Show this help message"
    echo ""
    echo "Examples:"
    echo "  sudo $0 production    # Full production deployment"
    echo "  sudo $0 test          # Test environment with web UI"
    echo "  $0 local              # Local development only"
}

# Function to validate environment variables
validate_env() {
    print_step "Validating environment variables"
    
    # Check if .env file exists
    if [ ! -f .env ]; then
        if [ -f .env.example ]; then
            print_info "Creating .env file from .env.example..."
            cp .env.example .env
            print_warning "Please edit .env with your configuration"
        else
            print_error ".env file not found and no .env.example available"
        fi
    fi
    
    # Load environment variables
    if [ -f .env ]; then
        export $(cat .env | grep -v '^#' | xargs)
    fi
    
    # Check required environment variables
    local required_vars=(
        "DB_HOST"
        "DB_USER"
        "DB_PASS"
        "DB_NAME"
    )
    
    for var in "${required_vars[@]}"; do
        if [ -z "${!var}" ]; then
            print_error "Required environment variable $var is not set"
        fi
    done
    
    print_status "Environment variables validated"
}

# Function to create test environment file
create_test_env() {
    print_info "Creating .env.test file for test environment..."
    
    cat > .env.test << EOF
DB_HOST=localhost
DB_DATABASE=garden_sensors
DB_USER=root
DB_PASS=newrootpassword
CACHE_DIR=/tmp/cache
CACHE_ENABLED=true
CACHE_TTL=3600
RATE_LIMIT_ENABLED=true
RATE_LIMIT_REQUESTS=100
RATE_LIMIT_WINDOW=3600
LOG_FILE=/tmp/garden_sensors.log
LOG_LEVEL=debug
LOG_MAX_SIZE=10485760
LOG_MAX_FILES=5
TESTING=true
EOF
    
    print_status "Test environment file created"
}

# Function to check if running as root
check_root() {
    if [ "$EUID" -ne 0 ]; then
        print_error "This setup requires root privileges (use sudo)"
    fi
    print_status "Root privileges confirmed"
}

# Function to install system dependencies
install_system_deps() {
    print_step "Installing system dependencies"
    
    # Update package list
    print_info "Updating package list..."
    apt-get update || print_error "Failed to update package list"
    
    # Install required packages
    print_info "Installing required packages..."
    apt-get install -y \
        python3 \
        python3-pip \
        python3-venv \
        mysql-server \
        apache2 \
        php \
        php-mysql \
        php-curl \
        php-json \
        php-mbstring \
        php-xml \
        php-zip \
        composer \
        || print_error "Failed to install system packages"
    
    print_status "System dependencies installed successfully"
}

# Function to setup Python virtual environment
setup_python_env() {
    print_step "Setting up Python virtual environment"

    # Prefer explicitly configured or user-writable venvs.
    if detect_python_venv; then
        print_info "Using existing virtual environment at $PYTHON_VENV_PATH"
    else
        print_info "Creating new virtual environment at .venv..."
        python3 -m venv .venv || print_error "Failed to create virtual environment"
        PYTHON_VENV_PATH=".venv"
    fi

    # If detected venv is not writable, fall back to .venv.
    if [ ! -w "$PYTHON_VENV_PATH" ]; then
        print_warning "Virtual environment '$PYTHON_VENV_PATH' is not writable, switching to .venv"
        if [ ! -d ".venv" ]; then
            python3 -m venv .venv || print_error "Failed to create fallback .venv"
        fi
        PYTHON_VENV_PATH=".venv"
    fi

    # Install Python dependencies
    print_info "Installing Python dependencies in $PYTHON_VENV_PATH..."
    "$PYTHON_VENV_PATH/bin/pip" install -r requirements.txt || print_error "Failed to install Python dependencies"
    
    print_status "Python environment setup completed"
}

# Function to setup MySQL database
setup_mysql() {
    print_step "Setting up MySQL database"
    
    # Ensure MySQL service is running
    if ! systemctl is-active --quiet mysql; then
        print_info "Starting MySQL service..."
        systemctl start mysql || print_error "Failed to start MySQL service"
        sleep 5  # Wait for MySQL to fully start
    else
        print_info "MySQL service is already running"
    fi
    
    # Check if production database exists
    print_info "Checking database existence..."
    if mysql -u root -pnewrootpassword -e "USE garden_sensors;" 2>/dev/null; then
        print_info "Production database 'garden_sensors' already exists"
    else
        print_info "Creating production database..."
        mysql -u root -pnewrootpassword -e "CREATE DATABASE IF NOT EXISTS garden_sensors CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" || print_error "Failed to create production database"
    fi
    
    # Deploy database schema
    print_info "Deploying database schema..."
    
    # Deploy production schema if schema file exists
    if [ -f "database/schema.sql" ]; then
        print_info "Deploying production schema..."
        mysql -u root -pnewrootpassword garden_sensors < database/schema.sql || print_warning "Failed to deploy production schema"
    fi
    
    # Run migrations if they exist
    if [ -d "database/migrations" ]; then
        print_info "Running database migrations..."
        for migration in database/migrations/*.sql; do
            if [ -f "$migration" ]; then
                print_info "Running migration: $(basename "$migration")"
                mysql -u root -pnewrootpassword garden_sensors < "$migration" || print_warning "Failed to run migration: $(basename "$migration")"
            fi
        done
    fi
    
    # Create views if they exist
    if [ -f "database/create_sensor_readings_view.sql" ]; then
        print_info "Creating sensor_readings view..."
        mysql -u root -pnewrootpassword garden_sensors < database/create_sensor_readings_view.sql || print_warning "Failed to create sensor_readings view"
    fi
    
    # Ensure API directory exists and has correct permissions
    print_info "Setting up API directory..."
    mkdir -p public/api
    chmod 755 public/api || print_warning "Failed to set API directory permissions"
    
    # Ensure Python plot API script is executable
    if [ -f "python/generate_plot_api.py" ]; then
        print_info "Setting up Python plot API script..."
        chmod +x python/generate_plot_api.py || print_warning "Failed to make plot API script executable"
    fi
    
    # Note: PHP setup script is not used as it uses production schema
    # Test database setup is handled directly by this script
    
    # Verify database setup
    print_info "Verifying database setup..."
    local required_tables=("users" "sensors" "readings" "plants" "plant_sensors" "sensor_readings")
    local missing_tables=()
    
    for table in "${required_tables[@]}"; do
        if mysql -u root -pnewrootpassword garden_sensors -e "SHOW TABLES LIKE '$table';" 2>/dev/null | grep -q "$table"; then
            print_info "✓ Table '$table' exists"
        else
            print_warning "✗ Table '$table' is missing"
            missing_tables+=("$table")
        fi
    done
    
    if [ ${#missing_tables[@]} -eq 0 ]; then
        print_status "All required database tables verified"
    else
        print_warning "Some database tables may not be properly created: ${missing_tables[*]}"
    fi
    
    # Note: Tests now use production database, so no separate test database verification needed
    
    # Setup database user permissions for testing
    print_info "Setting up database user permissions..."
    mysql -u root -pnewrootpassword -e "GRANT ALL PRIVILEGES ON garden_sensors.* TO 'garden_sensors'@'localhost';" 2>/dev/null || print_warning "Failed to grant permissions to garden_sensors user"
    mysql -u root -pnewrootpassword -e "GRANT ALL PRIVILEGES ON garden_sensors.* TO 'garden_user'@'localhost';" 2>/dev/null || print_warning "Failed to grant permissions to garden_user"
    mysql -u root -pnewrootpassword -e "FLUSH PRIVILEGES;" || print_warning "Failed to flush privileges"
    
    print_status "MySQL setup completed"
}

# Function to deploy to web root
deploy_to_web_root() {
    print_step "Deploying to web root"
    
    # Check if we're already in the web directory
    if [ "$(pwd)" = "/var/www/html/garden-sensors" ]; then
        print_info "Already in web directory, skipping file copy"
    else
        # For local setup without elevated permissions, run in place.
        if [ ! -w "/var/www/html" ]; then
            print_warning "No write permission to /var/www/html; using current directory as deployment root"
            print_status "Application will run from $(pwd)"
            return 0
        fi

        # Create web directory
        print_info "Creating web directory..."
        mkdir -p /var/www/html/garden-sensors || print_error "Failed to create web directory"
        
        # Copy application files
        print_info "Copying application files..."
        cp -r . /var/www/html/garden-sensors/ || print_error "Failed to copy application files"
    fi
    
    # Set proper permissions
    print_info "Setting file permissions..."
    chown -R www-data:www-data /var/www/html/garden-sensors || print_error "Failed to set ownership"
    chmod -R 755 /var/www/html/garden-sensors || print_error "Failed to set file permissions"
    
    # Ensure API directory and Python scripts have correct permissions
    print_info "Setting up API and Python script permissions..."
    mkdir -p /var/www/html/garden-sensors/public/api
    chmod 755 /var/www/html/garden-sensors/public/api
    if [ -f "/var/www/html/garden-sensors/python/generate_plot_api.py" ]; then
        chmod +x /var/www/html/garden-sensors/python/generate_plot_api.py
    fi
    
    print_status "Application deployed to /var/www/html/garden-sensors"
}

# Function to setup Apache configuration
setup_apache() {
    print_step "Setting up Apache configuration"

    if [ ! -w "/etc/apache2/sites-available" ]; then
        print_warning "No permission to configure Apache in this environment; skipping Apache setup"
        return 0
    fi
    
    # Enable required Apache modules
    print_info "Enabling required Apache modules..."
    a2enmod rewrite || print_error "Failed to enable rewrite module"
    a2enmod headers || print_error "Failed to enable headers module"
    a2enmod php8.3 || print_error "Failed to enable PHP module"
    
    # Create Apache virtual host configuration
    print_info "Creating Apache virtual host configuration..."
    cat > /etc/apache2/sites-available/garden-sensors.conf << EOF
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot /var/www/html/garden-sensors/public
    
    <Directory /var/www/html/garden-sensors/public>
        AllowOverride All
        Require all granted
        DirectoryIndex index.php index.html
    </Directory>
    
    # Security headers
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    
    # Logging
    ErrorLog \${APACHE_LOG_DIR}/garden-sensors_error.log
    CustomLog \${APACHE_LOG_DIR}/garden-sensors_access.log combined
</VirtualHost>
EOF
    
    # Enable the site
    a2ensite garden-sensors.conf || print_error "Failed to enable site"
    
    # Restart Apache
    print_info "Restarting Apache..."
    systemctl restart apache2 || print_error "Failed to restart Apache"
    
    print_status "Apache configuration completed"
}

# Function to install PHP dependencies
install_php_deps() {
    print_step "Installing PHP dependencies"
    
    print_info "Installing Composer dependencies..."
    if [ -d "vendor" ] && [ ! -w "vendor" ]; then
        print_warning "vendor/ is not writable in this environment; using existing Composer dependencies"
        if [ -f "./vendor/bin/phpunit" ]; then
            print_status "Existing Composer dependencies detected"
            return 0
        fi
        print_error "vendor/ is not writable and required dependencies are missing"
    fi

    echo "yes" | composer install || print_error "Failed to install Composer dependencies"
    
    print_status "PHP dependencies installed successfully"
}

# Function to seed default data
seed_default_data() {
    print_step "Seeding default data"

    # Ensure admin user exists with id 1
    print_info "Ensuring default admin user exists..."
    mysql -u root -pnewrootpassword garden_sensors -e "\
        INSERT INTO users (username, password_hash, email)\
        SELECT 'admin', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com'\
        WHERE NOT EXISTS (SELECT 1 FROM users WHERE id = 1 OR username = 'admin');\
        UPDATE users SET id = 1 WHERE username = 'admin' AND id <> 1;\
    " 2>/dev/null || print_warning "Failed to seed admin user"

    # Seed baseline sensors and plant-specific sensors used by dashboard/plot test data.
    print_info "Seeding sample sensors..."
    mysql -u root -pnewrootpassword garden_sensors -e "\
        INSERT INTO sensors (name, type, location, description, unit, plot_type, user_id)\
        SELECT 'Temperature Sensor 1', 'temperature', 'Greenhouse 1', 'Main temperature sensor in greenhouse 1', '°C', 'temperature', 1\
        WHERE NOT EXISTS (SELECT 1 FROM sensors WHERE name = 'Temperature Sensor 1');\
        INSERT INTO sensors (name, type, location, description, unit, plot_type, user_id)\
        SELECT 'Humidity Sensor 1', 'humidity', 'Greenhouse 1', 'Main humidity sensor in greenhouse 1', '%', 'humidity', 1\
        WHERE NOT EXISTS (SELECT 1 FROM sensors WHERE name = 'Humidity Sensor 1');\
        INSERT INTO sensors (name, type, location, description, unit, plot_type, user_id)\
        SELECT 'Soil Moisture 1', 'moisture', 'Greenhouse 1', 'Soil moisture sensor for plant bed 1', '%', 'moisture', 1\
        WHERE NOT EXISTS (SELECT 1 FROM sensors WHERE name = 'Soil Moisture 1');\
        INSERT INTO sensors (name, type, location, description, unit, plot_type, user_id)\
        SELECT 'Tomato Temperature Sensor', 'temperature', 'Greenhouse 1', 'Seeded sample sensor for Tomato Plant', '°C', 'temperature', 1\
        WHERE NOT EXISTS (SELECT 1 FROM sensors WHERE name = 'Tomato Temperature Sensor');\
        INSERT INTO sensors (name, type, location, description, unit, plot_type, user_id)\
        SELECT 'Tomato Humidity Sensor', 'humidity', 'Greenhouse 1', 'Seeded sample sensor for Tomato Plant', '%', 'humidity', 1\
        WHERE NOT EXISTS (SELECT 1 FROM sensors WHERE name = 'Tomato Humidity Sensor');\
        INSERT INTO sensors (name, type, location, description, unit, plot_type, user_id)\
        SELECT 'Tomato Moisture Sensor', 'moisture', 'Greenhouse 1', 'Seeded sample sensor for Tomato Plant', '%', 'moisture', 1\
        WHERE NOT EXISTS (SELECT 1 FROM sensors WHERE name = 'Tomato Moisture Sensor');\
        INSERT INTO sensors (name, type, location, description, unit, plot_type, user_id)\
        SELECT 'Basil Temperature Sensor', 'temperature', 'Greenhouse 1', 'Seeded sample sensor for Basil Plant', '°C', 'temperature', 1\
        WHERE NOT EXISTS (SELECT 1 FROM sensors WHERE name = 'Basil Temperature Sensor');\
        INSERT INTO sensors (name, type, location, description, unit, plot_type, user_id)\
        SELECT 'Basil Humidity Sensor', 'humidity', 'Greenhouse 1', 'Seeded sample sensor for Basil Plant', '%', 'humidity', 1\
        WHERE NOT EXISTS (SELECT 1 FROM sensors WHERE name = 'Basil Humidity Sensor');\
        INSERT INTO sensors (name, type, location, description, unit, plot_type, user_id)\
        SELECT 'Basil Moisture Sensor', 'moisture', 'Greenhouse 1', 'Seeded sample sensor for Basil Plant', '%', 'moisture', 1\
        WHERE NOT EXISTS (SELECT 1 FROM sensors WHERE name = 'Basil Moisture Sensor');\
        INSERT INTO sensors (name, type, location, description, unit, plot_type, user_id)\
        SELECT 'Lettuce Temperature Sensor', 'temperature', 'Greenhouse 2', 'Seeded sample sensor for Lettuce Plant', '°C', 'temperature', 1\
        WHERE NOT EXISTS (SELECT 1 FROM sensors WHERE name = 'Lettuce Temperature Sensor');\
        INSERT INTO sensors (name, type, location, description, unit, plot_type, user_id)\
        SELECT 'Lettuce Humidity Sensor', 'humidity', 'Greenhouse 2', 'Seeded sample sensor for Lettuce Plant', '%', 'humidity', 1\
        WHERE NOT EXISTS (SELECT 1 FROM sensors WHERE name = 'Lettuce Humidity Sensor');\
        INSERT INTO sensors (name, type, location, description, unit, plot_type, user_id)\
        SELECT 'Lettuce Moisture Sensor', 'moisture', 'Greenhouse 2', 'Seeded sample sensor for Lettuce Plant', '%', 'moisture', 1\
        WHERE NOT EXISTS (SELECT 1 FROM sensors WHERE name = 'Lettuce Moisture Sensor');\
    " 2>/dev/null || print_warning "Failed to seed sample sensors"

    # Seed sample plants for admin user if none exist
    print_info "Seeding sample plants if none exist..."
    mysql -u root -pnewrootpassword garden_sensors -e "\
        INSERT INTO plants (name, species, location, min_soil_moisture, max_soil_moisture, watering_frequency, status, user_id)\
        SELECT 'Tomato Plant', 'Solanum lycopersicum', 'Greenhouse 1', 40, 80, 24, 'active', 1\
        WHERE NOT EXISTS (SELECT 1 FROM plants);\
        INSERT INTO plants (name, species, location, min_soil_moisture, max_soil_moisture, watering_frequency, status, user_id)\
        SELECT 'Basil Plant', 'Ocimum basilicum', 'Greenhouse 1', 35, 70, 12, 'active', 1\
        WHERE (SELECT COUNT(*) FROM plants) = 1;\
        INSERT INTO plants (name, species, location, min_soil_moisture, max_soil_moisture, watering_frequency, status, user_id)\
        SELECT 'Lettuce Plant', 'Lactuca sativa', 'Greenhouse 2', 45, 75, 18, 'active', 1\
        WHERE (SELECT COUNT(*) FROM plants) = 2;\
    " 2>/dev/null || print_warning "Failed to seed sample plants"

    # Link sample sensors to sample plants when both exist.
    print_info "Linking sample sensors to sample plants..."
    mysql -u root -pnewrootpassword garden_sensors -e "\
        INSERT IGNORE INTO plant_sensors (sensor_id, plant_id, water_amount)\
        SELECT s.id, p.id, 250\
        FROM sensors s\
        JOIN plants p ON p.name = 'Tomato Plant'\
        WHERE s.name = 'Soil Moisture 1';\
        INSERT IGNORE INTO plant_sensors (sensor_id, plant_id, water_amount)\
        SELECT s.id, p.id, 200\
        FROM sensors s\
        JOIN plants p ON p.name = 'Basil Plant'\
        WHERE s.name = 'Humidity Sensor 1';\
        INSERT IGNORE INTO plant_sensors (sensor_id, plant_id, water_amount)\
        SELECT s.id, p.id, 220\
        FROM sensors s\
        JOIN plants p ON p.name = 'Lettuce Plant'\
        WHERE s.name = 'Temperature Sensor 1';\
        INSERT IGNORE INTO plant_sensors (sensor_id, plant_id, water_amount)\
        SELECT s.id, p.id, 250\
        FROM sensors s\
        JOIN plants p ON p.name = 'Tomato Plant'\
        WHERE s.name IN ('Tomato Temperature Sensor', 'Tomato Humidity Sensor', 'Tomato Moisture Sensor');\
        INSERT IGNORE INTO plant_sensors (sensor_id, plant_id, water_amount)\
        SELECT s.id, p.id, 220\
        FROM sensors s\
        JOIN plants p ON p.name = 'Basil Plant'\
        WHERE s.name IN ('Basil Temperature Sensor', 'Basil Humidity Sensor', 'Basil Moisture Sensor');\
        INSERT IGNORE INTO plant_sensors (sensor_id, plant_id, water_amount)\
        SELECT s.id, p.id, 230\
        FROM sensors s\
        JOIN plants p ON p.name = 'Lettuce Plant'\
        WHERE s.name IN ('Lettuce Temperature Sensor', 'Lettuce Humidity Sensor', 'Lettuce Moisture Sensor');\
    " 2>/dev/null || print_warning "Failed to link sample plants and sensors"

    # Seed deterministic dashboard readings for each plant + metric.
    # We replace only readings for seeded sample sensors so deploy always has testable graph/metrics data.
    print_info "Seeding sample readings for each plant and metric..."
    mysql -u root -pnewrootpassword garden_sensors -e "\
        DELETE r FROM readings r\
        JOIN sensors s ON s.id = r.sensor_id\
        WHERE s.name IN (\
            'Tomato Temperature Sensor', 'Tomato Humidity Sensor', 'Tomato Moisture Sensor',\
            'Basil Temperature Sensor', 'Basil Humidity Sensor', 'Basil Moisture Sensor',\
            'Lettuce Temperature Sensor', 'Lettuce Humidity Sensor', 'Lettuce Moisture Sensor'\
        );\
        INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)\
        SELECT s.id, 24.5, '°C', 24.5, 62.0, DATE_SUB(NOW(), INTERVAL 6 DAY) FROM sensors s WHERE s.name = 'Tomato Temperature Sensor';\
        INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)\
        SELECT s.id, 25.0, '°C', 25.0, 61.5, DATE_SUB(NOW(), INTERVAL 3 DAY) FROM sensors s WHERE s.name = 'Tomato Temperature Sensor';\
        INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)\
        SELECT s.id, 24.2, '°C', 24.2, 63.2, DATE_SUB(NOW(), INTERVAL 1 DAY) FROM sensors s WHERE s.name = 'Tomato Temperature Sensor';\
        INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)\
        SELECT s.id, 62.0, '%', 24.0, 62.0, DATE_SUB(NOW(), INTERVAL 6 DAY) FROM sensors s WHERE s.name = 'Tomato Humidity Sensor';\
        INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)\
        SELECT s.id, 64.0, '%', 24.8, 64.0, DATE_SUB(NOW(), INTERVAL 3 DAY) FROM sensors s WHERE s.name = 'Tomato Humidity Sensor';\
        INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)\
        SELECT s.id, 61.0, '%', 24.3, 61.0, DATE_SUB(NOW(), INTERVAL 1 DAY) FROM sensors s WHERE s.name = 'Tomato Humidity Sensor';\
        INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)\
        SELECT s.id, 55.0, '%', 23.8, 60.0, DATE_SUB(NOW(), INTERVAL 6 DAY) FROM sensors s WHERE s.name = 'Tomato Moisture Sensor';\
        INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)\
        SELECT s.id, 53.0, '%', 24.6, 62.0, DATE_SUB(NOW(), INTERVAL 3 DAY) FROM sensors s WHERE s.name = 'Tomato Moisture Sensor';\
        INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)\
        SELECT s.id, 57.0, '%', 24.1, 61.0, DATE_SUB(NOW(), INTERVAL 1 DAY) FROM sensors s WHERE s.name = 'Tomato Moisture Sensor';\
        INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)\
        SELECT s.id, 22.4, '°C', 22.4, 58.0, DATE_SUB(NOW(), INTERVAL 6 DAY) FROM sensors s WHERE s.name = 'Basil Temperature Sensor';\
        INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)\
        SELECT s.id, 23.1, '°C', 23.1, 57.0, DATE_SUB(NOW(), INTERVAL 3 DAY) FROM sensors s WHERE s.name = 'Basil Temperature Sensor';\
        INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)\
        SELECT s.id, 22.8, '°C', 22.8, 59.0, DATE_SUB(NOW(), INTERVAL 1 DAY) FROM sensors s WHERE s.name = 'Basil Temperature Sensor';\
        INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)\
        SELECT s.id, 58.0, '%', 22.5, 58.0, DATE_SUB(NOW(), INTERVAL 6 DAY) FROM sensors s WHERE s.name = 'Basil Humidity Sensor';\
        INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)\
        SELECT s.id, 56.0, '%', 23.0, 56.0, DATE_SUB(NOW(), INTERVAL 3 DAY) FROM sensors s WHERE s.name = 'Basil Humidity Sensor';\
        INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)\
        SELECT s.id, 60.0, '%', 22.9, 60.0, DATE_SUB(NOW(), INTERVAL 1 DAY) FROM sensors s WHERE s.name = 'Basil Humidity Sensor';\
        INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)\
        SELECT s.id, 49.0, '%', 22.3, 57.0, DATE_SUB(NOW(), INTERVAL 6 DAY) FROM sensors s WHERE s.name = 'Basil Moisture Sensor';\
        INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)\
        SELECT s.id, 51.0, '%', 22.8, 58.0, DATE_SUB(NOW(), INTERVAL 3 DAY) FROM sensors s WHERE s.name = 'Basil Moisture Sensor';\
        INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)\
        SELECT s.id, 50.0, '%', 22.6, 59.0, DATE_SUB(NOW(), INTERVAL 1 DAY) FROM sensors s WHERE s.name = 'Basil Moisture Sensor';\
        INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)\
        SELECT s.id, 21.5, '°C', 21.5, 66.0, DATE_SUB(NOW(), INTERVAL 6 DAY) FROM sensors s WHERE s.name = 'Lettuce Temperature Sensor';\
        INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)\
        SELECT s.id, 21.9, '°C', 21.9, 65.0, DATE_SUB(NOW(), INTERVAL 3 DAY) FROM sensors s WHERE s.name = 'Lettuce Temperature Sensor';\
        INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)\
        SELECT s.id, 22.2, '°C', 22.2, 64.0, DATE_SUB(NOW(), INTERVAL 1 DAY) FROM sensors s WHERE s.name = 'Lettuce Temperature Sensor';\
        INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)\
        SELECT s.id, 66.0, '%', 21.6, 66.0, DATE_SUB(NOW(), INTERVAL 6 DAY) FROM sensors s WHERE s.name = 'Lettuce Humidity Sensor';\
        INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)\
        SELECT s.id, 65.0, '%', 22.0, 65.0, DATE_SUB(NOW(), INTERVAL 3 DAY) FROM sensors s WHERE s.name = 'Lettuce Humidity Sensor';\
        INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)\
        SELECT s.id, 64.0, '%', 22.1, 64.0, DATE_SUB(NOW(), INTERVAL 1 DAY) FROM sensors s WHERE s.name = 'Lettuce Humidity Sensor';\
        INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)\
        SELECT s.id, 47.0, '%', 21.3, 65.0, DATE_SUB(NOW(), INTERVAL 6 DAY) FROM sensors s WHERE s.name = 'Lettuce Moisture Sensor';\
        INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)\
        SELECT s.id, 45.0, '%', 21.8, 64.0, DATE_SUB(NOW(), INTERVAL 3 DAY) FROM sensors s WHERE s.name = 'Lettuce Moisture Sensor';\
        INSERT INTO readings (sensor_id, value, unit, temperature, humidity, created_at)\
        SELECT s.id, 48.0, '%', 22.0, 63.0, DATE_SUB(NOW(), INTERVAL 1 DAY) FROM sensors s WHERE s.name = 'Lettuce Moisture Sensor';\
    " 2>/dev/null || print_warning "Failed to seed sample readings"

    print_status "Default data seeding completed"
}

# Function to verify database setup
verify_database() {
    print_step "Verifying database setup"
    
    print_info "Checking database connectivity..."
    if mysql -u root -pnewrootpassword -e "SELECT 1;" 2>/dev/null; then
        print_status "MySQL connection successful"
    else
        print_error "MySQL connection failed"
    fi
    
    print_info "Checking production database..."
    if mysql -u root -pnewrootpassword garden_sensors -e "SHOW TABLES;" 2>/dev/null | grep -q "users"; then
        print_status "Production database tables verified"
    else
        print_warning "Production database tables may be missing"
    fi
    
    print_info "Checking production database..."
    if mysql -u root -pnewrootpassword garden_sensors -e "SHOW TABLES;" 2>/dev/null | grep -q "users"; then
        print_status "Production database tables verified"
    else
        print_warning "Production database tables may be missing"
    fi
    
    print_info "Checking database permissions..."
    if mysql -u root -pnewrootpassword -e "SHOW GRANTS FOR 'root'@'localhost';" 2>/dev/null | grep -q "ALL PRIVILEGES"; then
        print_status "Database permissions verified"
    else
        print_warning "Database permissions may be insufficient"
    fi
    
    print_status "Database verification completed"
}

# Function to prepare test environment
prepare_test_environment() {
    print_info "Preparing test environment..."
    
    # Determine the working directory (deployment directory if deployed, or current directory)
    local work_dir="/var/www/html/garden-sensors"
    if [ ! -d "$work_dir" ]; then
        work_dir="$(pwd)"
    fi
    
    # Ensure test log files are writable (try multiple approaches)
    print_info "Setting up log file permissions..."
    
    # Method 1: Try to create/update with sudo
    sudo touch /tmp/garden_sensors.log 2>/dev/null || touch /tmp/garden_sensors.log 2>/dev/null || true
    sudo chmod 666 /tmp/garden_sensors.log 2>/dev/null || chmod 666 /tmp/garden_sensors.log 2>/dev/null || true
    sudo chown www-data:www-data /tmp/garden_sensors.log 2>/dev/null || chown www-data:www-data /tmp/garden_sensors.log 2>/dev/null || true
    
    # Verify permissions
    if [ -f "/tmp/garden_sensors.log" ]; then
        local perms=$(stat -c "%a" /tmp/garden_sensors.log 2>/dev/null || echo "unknown")
        print_info "Log file permissions: $perms"
    else
        print_warning "Could not create log file, tests may fail"
    fi
    
    print_status "Test environment prepared"
}

# Function to run tests
run_tests() {
    print_step "Running tests"
    
    local php_test_result=0
    local python_test_result=0
    
    # Prepare test environment (log files, permissions, etc.)
    prepare_test_environment
    
    # Ensure we're in the deployment directory for tests
    if [ -d "/var/www/html/garden-sensors" ]; then
        cd /var/www/html/garden-sensors || print_error "Failed to change to deployment directory"
    fi
    
    print_info "Running PHP unit tests..."
    if ./vendor/bin/phpunit --testdox; then
        print_status "PHP tests passed"
    else
        php_test_result=$?
        print_error "PHP tests failed"
        return $php_test_result
    fi
    
    print_info "Running Python tests..."
    if detect_python_venv; then
        source "$PYTHON_VENV_PATH/bin/activate"
        if python -m pytest tests/python/ -v; then
            print_status "Python tests passed"
        else
            python_test_result=$?
            print_error "Python tests failed"
            return $python_test_result
        fi
    else
        print_warning "Python virtual environment not found, skipping Python tests"
    fi
    
    print_status "All tests completed successfully"
    return 0
}

# Function to cleanup test data from database
cleanup_test_data() {
    print_step "Cleaning up test data from database"
    
    # Use truncation for a completely fresh start (faster and more thorough)
    local truncate_script=""
    if [ -f "database/truncate_all_data.sql" ]; then
        truncate_script="database/truncate_all_data.sql"
    elif [ -f "/var/www/html/garden-sensors/database/truncate_all_data.sql" ]; then
        truncate_script="/var/www/html/garden-sensors/database/truncate_all_data.sql"
    elif [ -f "$(pwd)/database/truncate_all_data.sql" ]; then
        truncate_script="$(pwd)/database/truncate_all_data.sql"
    fi
    
    if [ -n "$truncate_script" ]; then
        print_info "Truncating all data tables for fresh deployment..."
        mysql -u root -pnewrootpassword garden_sensors < "$truncate_script" || print_warning "Failed to truncate data tables"
        
        # Re-seed essential data after truncation
        print_info "Re-seeding essential data (admin user, settings)..."
        seed_default_data
        
        # Verify cleanup - should be empty or only have seeded data
        local total_sensors=$(mysql -u root -pnewrootpassword garden_sensors -sN -e "SELECT COUNT(*) FROM sensors;" 2>/dev/null || echo "0")
        local total_readings=$(mysql -u root -pnewrootpassword garden_sensors -sN -e "SELECT COUNT(*) FROM readings;" 2>/dev/null || echo "0")
        local admin_user_exists=$(mysql -u root -pnewrootpassword garden_sensors -sN -e "SELECT COUNT(*) FROM users WHERE username = 'admin';" 2>/dev/null || echo "0")
        
        if [ "$admin_user_exists" -eq 1 ]; then
            print_status "Database truncated and re-seeded successfully (sensors: $total_sensors, readings: $total_readings)"
        else
            print_warning "Truncation completed but admin user may not exist"
        fi
    else
        # Fallback to selective cleanup if truncate script not found
        print_warning "Truncate script not found, falling back to selective cleanup..."
        
        local cleanup_script=""
        if [ -f "database/cleanup_test_data.sql" ]; then
            cleanup_script="database/cleanup_test_data.sql"
        elif [ -f "/var/www/html/garden-sensors/database/cleanup_test_data.sql" ]; then
            cleanup_script="/var/www/html/garden-sensors/database/cleanup_test_data.sql"
        elif [ -f "$(pwd)/database/cleanup_test_data.sql" ]; then
            cleanup_script="$(pwd)/database/cleanup_test_data.sql"
        fi
        
        if [ -n "$cleanup_script" ]; then
            print_info "Removing test data using: $cleanup_script"
            
            # Count test sensors before cleanup
            local test_sensors_before=$(mysql -u root -pnewrootpassword garden_sensors -sN -e "SELECT COUNT(*) FROM sensors WHERE name LIKE '%Test%' OR name LIKE 'Test Pin Sensor%' OR name LIKE 'Test Reading Sensor%';" 2>/dev/null || echo "0")
            
            mysql -u root -pnewrootpassword garden_sensors < "$cleanup_script" || print_warning "Failed to cleanup test data"
            
            # Verify cleanup
            local test_sensors_after=$(mysql -u root -pnewrootpassword garden_sensors -sN -e "SELECT COUNT(*) FROM sensors WHERE name LIKE '%Test%' OR name LIKE 'Test Pin Sensor%' OR name LIKE 'Test Reading Sensor%';" 2>/dev/null || echo "0")
            local test_user_count=$(mysql -u root -pnewrootpassword garden_sensors -sN -e "SELECT COUNT(*) FROM users WHERE username LIKE 'testuser_%' OR email LIKE 'test_%@example.com';" 2>/dev/null || echo "0")
            
            if [ "$test_sensors_after" -eq 0 ] && [ "$test_user_count" -eq 0 ]; then
                print_status "Test data cleanup verified (removed $test_sensors_before test sensors)"
            else
                print_warning "Some test data may still exist (sensors: $test_sensors_after, users: $test_user_count)"
            fi
        else
            print_warning "No cleanup script found, skipping test data cleanup"
        fi
    fi
}

# Production setup
setup_production() {
    print_step "Starting Garden Sensors Production Setup"
    
    check_root
    validate_env
    install_system_deps
    setup_python_env
    setup_mysql
    seed_default_data
    verify_database
    deploy_to_web_root
    install_php_deps
    setup_apache
    
    # Prepare test environment before running tests
    prepare_test_environment
    
    # Run tests and cleanup
    print_info "Running test suite to verify deployment..."
    if run_tests; then
        print_status "All tests passed successfully"
        cleanup_test_data
    else
        print_error "Tests failed - deployment may not be production-ready"
        print_warning "Test data cleanup skipped due to test failures"
        return 1
    fi
    
    print_step "Production Setup Completed Successfully"
    print_info "Application is deployed at /var/www/html/garden-sensors"
    print_info "Database has been tested and cleaned - ready for production use"
    print_info "You can access the web interface at http://localhost/garden-sensors"
}

# Test setup
setup_test() {
    print_step "Starting Garden Sensors Test Environment Setup"
    
    check_root
    validate_env
    create_test_env
    deploy_to_web_root
    install_php_deps
    setup_python_env
    setup_mysql
    seed_default_data
    verify_database
    setup_apache
    
    # Prepare test environment before running tests
    prepare_test_environment
    
    run_tests
    
    print_step "Test Environment Setup Completed Successfully"
    print_info "Application is deployed at /var/www/html/garden-sensors"
    print_info "You can access the web interface at http://localhost/garden-sensors"
    print_info "To run tests again: cd /var/www/html/garden-sensors && ./vendor/bin/phpunit"
}

# Local setup
setup_local() {
    print_step "Starting Garden Sensors Local Development Setup"
    
    validate_env
    setup_python_env
    deploy_to_web_root
    install_php_deps
    setup_mysql
    seed_default_data
    verify_database
    setup_apache
    
    # Prepare test environment before running tests
    prepare_test_environment
    
    # Run tests and cleanup
    print_info "Running test suite to verify deployment..."
    if run_tests; then
        print_status "All tests passed successfully"
        cleanup_test_data
    else
        print_error "Tests failed - deployment may not be production-ready"
        print_warning "Test data cleanup skipped due to test failures"
        return 1
    fi
    
    print_step "Local Development Setup Completed Successfully"
    print_info "Application is deployed at /var/www/html/garden-sensors"
    print_info "Database has been tested and cleaned - ready for production use"
    print_info "You can access the web interface at http://localhost/garden-sensors"
}

# Main script
case "${1:-help}" in
    production)
        setup_production
        ;;
    test)
        setup_test
        ;;
    local)
        setup_local
        ;;
    help|--help|-h)
        show_usage
        ;;
    *)
        print_error "Unknown option: $1"
        show_usage
        ;;
esac 