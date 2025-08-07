#!/bin/bash

# Garden Sensors Setup Script
# This script provides different setup options for the Garden Sensors project

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

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
    
    # Create virtual environment if it doesn't exist
    if [ ! -d "venv" ]; then
        print_info "Creating new virtual environment..."
        python3 -m venv venv || print_error "Failed to create virtual environment"
    else
        print_info "Using existing virtual environment"
    fi
    
    # Install Python dependencies
    print_info "Installing Python dependencies..."
    venv/bin/pip install -r requirements.txt || print_error "Failed to install Python dependencies"
    
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
    
    # Note: PHP setup script is not used as it uses production schema
    # Test database setup is handled directly by this script
    
    # Verify database setup
    print_info "Verifying database setup..."
    if mysql -u root -pnewrootpassword garden_sensors -e "SHOW TABLES;" 2>/dev/null | grep -q "users"; then
        print_status "Production database setup verified"
    else
        print_warning "Production database tables may not be properly created"
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
    
    print_status "Application deployed to /var/www/html/garden-sensors"
}

# Function to setup Apache configuration
setup_apache() {
    print_step "Setting up Apache configuration"
    
    # Enable required Apache modules
    print_info "Enabling required Apache modules..."
    a2enmod rewrite || print_error "Failed to enable rewrite module"
    a2enmod headers || print_error "Failed to enable headers module"
    
    # Create Apache virtual host configuration
    print_info "Creating Apache virtual host configuration..."
    cat > /etc/apache2/sites-available/garden-sensors.conf << EOF
<VirtualHost *:80>
    ServerName garden-sensors.local
    DocumentRoot /var/www/html/garden-sensors/public
    
    <Directory /var/www/html/garden-sensors/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/garden-sensors-error.log
    CustomLog \${APACHE_LOG_DIR}/garden-sensors-access.log combined
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
    echo "yes" | composer install || print_error "Failed to install Composer dependencies"
    
    print_status "PHP dependencies installed successfully"
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

# Function to run tests
run_tests() {
    print_step "Running tests"
    
    print_info "Running PHP unit tests..."
    ./vendor/bin/phpunit || print_warning "PHP tests failed"
    
    print_info "Running Python tests..."
    source venv/bin/activate
    pytest || print_warning "Python tests failed"
    
    print_status "Tests completed"
}

# Production setup
setup_production() {
    print_step "Starting Garden Sensors Production Setup"
    
    check_root
    validate_env
    install_system_deps
    setup_python_env
    setup_mysql
    verify_database
    deploy_to_web_root
    install_php_deps
    setup_apache
    
    print_step "Production Setup Completed Successfully"
    print_info "Application is deployed at /var/www/html/garden-sensors"
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
    verify_database
    setup_apache
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
    install_php_deps
    setup_mysql
    verify_database
    
    print_step "Local Development Setup Completed Successfully"
    print_info "You can run tests with: ./vendor/bin/phpunit"
    print_info "You can run Python tests with: source venv/bin/activate && pytest"
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