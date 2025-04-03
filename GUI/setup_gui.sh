#!/bin/bash

# Garden Sensors GUI Setup Script
# This script automates the setup of the Garden Sensors GUI

# Text colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Print with color
print_color() {
    local color=$1
    local message=$2
    echo -e "${color}${message}${NC}"
}

# Print header
print_header() {
    print_color "$BLUE" "=================================================="
    print_color "$BLUE" "  Garden Sensors GUI Setup"
    print_color "$BLUE" "=================================================="
    echo ""
}

# Print section header
print_section() {
    print_color "$YELLOW" "==> $1"
    echo ""
}

# Print success message
print_success() {
    print_color "$GREEN" "✓ $1"
}

# Print error message
print_error() {
    print_color "$RED" "✗ $1"
}

# Print info message
print_info() {
    print_color "$BLUE" "ℹ $1"
}

# Check if running as root
check_root() {
    if [ "$EUID" -ne 0 ]; then
        print_error "This script must be run as root (use sudo)"
        exit 1
    fi
}

# Check system requirements
check_system() {
    print_section "Checking system requirements"
    
    # Check PHP version
    if command -v php &> /dev/null; then
        PHP_VERSION=$(php -r "echo PHP_VERSION;")
        print_info "PHP version: $PHP_VERSION"
        
        # Check if PHP version is 7.4 or higher
        if [[ "$(printf '%s\n' "7.4" "$PHP_VERSION" | sort -V | head -n1)" = "7.4" ]]; then
            print_success "PHP version is compatible"
        else
            print_error "PHP version 7.4 or higher is required"
            exit 1
        fi
    else
        print_error "PHP is not installed"
        exit 1
    fi
    
    # Check Apache
    if command -v apache2 &> /dev/null; then
        print_success "Apache is installed"
    else
        print_error "Apache is not installed"
        exit 1
    fi
    
    # Check MySQL
    if command -v mysql &> /dev/null; then
        print_success "MySQL is installed"
    else
        print_error "MySQL is not installed"
        exit 1
    fi
    
    # Check required PHP extensions
    print_info "Checking required PHP extensions"
    REQUIRED_EXTENSIONS=("mysqli" "json" "curl" "gd" "mbstring" "xml")
    MISSING_EXTENSIONS=()
    
    for ext in "${REQUIRED_EXTENSIONS[@]}"; do
        if php -m | grep -q "^$ext$"; then
            print_success "PHP extension: $ext"
        else
            print_error "Missing PHP extension: $ext"
            MISSING_EXTENSIONS+=("$ext")
        fi
    done
    
    if [ ${#MISSING_EXTENSIONS[@]} -gt 0 ]; then
        print_error "Please install the missing PHP extensions and try again"
        exit 1
    fi
}

# Install dependencies
install_dependencies() {
    print_section "Installing dependencies"
    
    # Install required packages
    print_info "Installing required packages"
    apt-get update
    apt-get install -y php-mysqli php-json php-curl php-gd php-mbstring php-xml
    
    # Install Composer if not installed
    if ! command -v composer &> /dev/null; then
        print_info "Installing Composer"
        curl -sS https://getcomposer.org/installer | php
        mv composer.phar /usr/local/bin/composer
    else
        print_success "Composer is already installed"
    fi
    
    # Install npm if not installed
    if ! command -v npm &> /dev/null; then
        print_info "Installing npm"
        apt-get install -y npm
    else
        print_success "npm is already installed"
    fi
    
    print_success "Dependencies installed successfully"
}

# Configure Apache
configure_apache() {
    print_section "Configuring Apache"
    
    # Enable required Apache modules
    print_info "Enabling required Apache modules"
    a2enmod rewrite
    a2enmod headers
    
    # Create Apache virtual host configuration
    print_info "Creating Apache virtual host configuration"
    cat > /etc/apache2/sites-available/garden-sensors.conf << EOF
<VirtualHost *:80>
    ServerName garden-sensors.local
    DocumentRoot /var/www/html/garden-sensors
    
    <Directory /var/www/html/garden-sensors>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/garden-sensors-error.log
    CustomLog \${APACHE_LOG_DIR}/garden-sensors-access.log combined
</VirtualHost>
EOF
    
    # Enable the site
    a2ensite garden-sensors.conf
    
    # Restart Apache
    print_info "Restarting Apache"
    systemctl restart apache2
    
    print_success "Apache configured successfully"
}

# Setup database
setup_database() {
    print_section "Setting up database"
    
    # Check if database exists
    if mysql -e "USE garden_sensors" 2>/dev/null; then
        print_info "Database 'garden_sensors' already exists"
        
        # Ask if user wants to recreate the database
        read -p "Do you want to recreate the database? (y/n): " RECREATE_DB
        if [[ "$RECREATE_DB" =~ ^[Yy]$ ]]; then
            mysql -e "DROP DATABASE garden_sensors"
        else
            print_info "Using existing database"
            return
        fi
    fi
    
    # Create database
    print_info "Creating database"
    mysql -e "CREATE DATABASE garden_sensors"
    
    # Import schema
    print_info "Importing database schema"
    if [ -f "../database/schema.sql" ]; then
        mysql garden_sensors < ../database/schema.sql
        print_success "Database schema imported successfully"
    else
        print_error "Schema file not found at ../database/schema.sql"
        exit 1
    fi
    
    # Create database user
    print_info "Creating database user"
    DB_USER="garden_user"
    DB_PASS=$(openssl rand -base64 12)
    
    mysql -e "CREATE USER '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS'"
    mysql -e "GRANT ALL PRIVILEGES ON garden_sensors.* TO '$DB_USER'@'localhost'"
    mysql -e "FLUSH PRIVILEGES"
    
    # Save database credentials
    print_info "Saving database credentials"
    cat > config/database.php << EOF
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'garden_sensors');
define('DB_USER', '$DB_USER');
define('DB_PASS', '$DB_PASS');
?>
EOF
    
    print_success "Database setup completed successfully"
    print_info "Database credentials saved to config/database.php"
}

# Setup file permissions
setup_permissions() {
    print_section "Setting up file permissions"
    
    # Set ownership
    print_info "Setting file ownership"
    chown -R www-data:www-data /var/www/html/garden-sensors
    
    # Set permissions
    print_info "Setting file permissions"
    find /var/www/html/garden-sensors -type d -exec chmod 755 {} \;
    find /var/www/html/garden-sensors -type f -exec chmod 644 {} \;
    
    # Make specific directories writable
    print_info "Making specific directories writable"
    chmod -R 775 /var/www/html/garden-sensors/cache
    chmod -R 775 /var/www/html/garden-sensors/logs
    
    print_success "File permissions set successfully"
}

# Install frontend dependencies
install_frontend_dependencies() {
    print_section "Installing frontend dependencies"
    
    # Install npm packages
    print_info "Installing npm packages"
    npm install
    
    # Install Composer packages
    print_info "Installing Composer packages"
    composer install
    
    print_success "Frontend dependencies installed successfully"
}

# Create default admin user
create_admin_user() {
    print_section "Creating default admin user"
    
    # Check if users table exists
    if mysql -e "USE garden_sensors; SELECT 1 FROM users LIMIT 1" 2>/dev/null; then
        print_info "Users table already exists"
        
        # Ask if user wants to create a new admin user
        read -p "Do you want to create a new admin user? (y/n): " CREATE_ADMIN
        if [[ ! "$CREATE_ADMIN" =~ ^[Yy]$ ]]; then
            print_info "Skipping admin user creation"
            return
        fi
    fi
    
    # Get admin credentials
    read -p "Enter admin username (default: admin): " ADMIN_USER
    ADMIN_USER=${ADMIN_USER:-admin}
    
    read -s -p "Enter admin password: " ADMIN_PASS
    echo ""
    
    # Hash password
    ADMIN_PASS_HASH=$(php -r "echo password_hash('$ADMIN_PASS', PASSWORD_DEFAULT);")
    
    # Insert admin user
    mysql -e "USE garden_sensors; INSERT INTO users (username, password, role) VALUES ('$ADMIN_USER', '$ADMIN_PASS_HASH', 'admin')"
    
    print_success "Admin user created successfully"
    print_info "Username: $ADMIN_USER"
}

# Setup cron jobs
setup_cron() {
    print_section "Setting up cron jobs"
    
    # Check if cron jobs already exist
    if crontab -l | grep -q "garden-sensors"; then
        print_info "Cron jobs already exist"
        
        # Ask if user wants to recreate the cron jobs
        read -p "Do you want to recreate the cron jobs? (y/n): " RECREATE_CRON
        if [[ ! "$RECREATE_CRON" =~ ^[Yy]$ ]]; then
            print_info "Skipping cron job setup"
            return
        fi
    fi
    
    # Create cron jobs
    print_info "Creating cron jobs"
    
    # Add cron job for sensor readings (every 5 minutes)
    (crontab -l 2>/dev/null; echo "*/5 * * * * php /var/www/html/garden-sensors/cron/collect_readings.php") | crontab -
    
    # Add cron job for alerts (every 15 minutes)
    (crontab -l 2>/dev/null; echo "*/15 * * * * php /var/www/html/garden-sensors/cron/check_alerts.php") | crontab -
    
    # Add cron job for data cleanup (daily at midnight)
    (crontab -l 2>/dev/null; echo "0 0 * * * php /var/www/html/garden-sensors/cron/cleanup_data.php") | crontab -
    
    print_success "Cron jobs set up successfully"
}

# Main function
main() {
    print_header
    check_root
    check_system
    install_dependencies
    configure_apache
    setup_database
    setup_permissions
    install_frontend_dependencies
    create_admin_user
    setup_cron
    
    print_section "Setup completed successfully"
    print_info "You can now access the Garden Sensors GUI at: http://localhost/garden-sensors"
    print_info "Default admin credentials:"
    print_info "Username: admin"
    print_info "Password: (the one you entered during setup)"
}

# Run main function
main 