#!/bin/bash

# Load environment variables
if [ -f .env ]; then
    export $(cat .env | grep -v '^#' | xargs)
else
    echo "Error: .env file not found"
    exit 1
fi

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

# Function to validate environment variables
validate_env() {
    print_step "Validating environment variables"
    
    # Check required environment variables
    local required_vars=(
        "DB_HOST"
        "DB_USER"
        "DB_NAME"
        "APACHE_DOMAIN"
    )
    
    for var in "${required_vars[@]}"; do
        if [ -z "${!var}" ]; then
            print_error "Required environment variable $var is not set"
        fi
    done
    
    print_status "Environment variables validated"
}

# Function to check if running as root
check_root() {
    print_step "Checking root privileges"
    if [ "$EUID" -ne 0 ]; then
        print_error "Please run as root (use sudo)"
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
    
    # Use the virtual environment's Python and pip directly
    VENV_PYTHON="venv/bin/python3"
    VENV_PIP="venv/bin/pip"
    
    # Install Python dependencies
    print_info "Installing Python dependencies from requirements.txt..."
    $VENV_PIP install -r requirements.txt || print_error "Failed to install Python dependencies"
    
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
    
    # Drop and recreate database and user
    print_info "Setting up database and user..."
    sudo mysql -e "DROP DATABASE IF EXISTS ${DB_NAME};"
    sudo mysql -e "DROP USER IF EXISTS '${DB_USER}'@'${DB_HOST}';"
    sudo mysql -e "CREATE DATABASE ${DB_NAME};"
    sudo mysql -e "CREATE USER '${DB_USER}'@'${DB_HOST}' IDENTIFIED BY '';"
    sudo mysql -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'${DB_HOST}';"
    sudo mysql -e "FLUSH PRIVILEGES;"
    
    # Deploy database schema
    print_info "Deploying database schema..."
    if [ -f SQLDeployScript.sql ]; then
        sudo mysql ${DB_NAME} < SQLDeployScript.sql || print_error "Failed to deploy database schema"
    else
        print_error "SQLDeployScript.sql not found"
        return 1
    fi
    
    print_status "MySQL setup completed"
}

# Function to setup application files
setup_application() {
    print_step "Setting up application files"
    
    # Create application directory structure
    print_info "Creating application directory structure..."
    sudo mkdir -p /var/www/html/garden-sensors/{public/{includes,assets,css,js,uploads},src,config,logs,cache}
    
    # Copy application files
    print_info "Copying application files..."
    sudo cp -r public/* /var/www/html/garden-sensors/public/
    sudo cp -r src/* /var/www/html/garden-sensors/src/
    
    # Ensure includes directory is properly set up
    print_info "Setting up includes directory..."
    sudo mkdir -p /var/www/html/garden-sensors/public/includes
    
    # Copy functions.php to the correct location
    if [ -f public/includes/functions.php ]; then
        sudo cp public/includes/functions.php /var/www/html/garden-sensors/public/includes/
    elif [ -f public/assets/includes/functions.php ]; then
        sudo cp public/assets/includes/functions.php /var/www/html/garden-sensors/public/includes/
    elif [ -f public/assets/functions.php ]; then
        sudo cp public/assets/functions.php /var/www/html/garden-sensors/public/includes/
    else
        print_error "functions.php not found in any expected location"
        return 1
    fi
    
    # Set proper permissions
    print_info "Setting file permissions..."
    sudo chown -R www-data:www-data /var/www/html/garden-sensors
    sudo chmod -R 755 /var/www/html/garden-sensors
    sudo chmod -R 775 /var/www/html/garden-sensors/logs
    sudo chmod -R 775 /var/www/html/garden-sensors/cache
    sudo chmod -R 775 /var/www/html/garden-sensors/public/uploads
    
    print_status "Application setup completed"
}

# Function to setup Apache web server
setup_apache() {
    print_step "Setting up Apache web server"
    
    # Enable required Apache modules
    print_info "Enabling Apache modules..."
    a2enmod rewrite
    a2enmod php8.3
    
    # Create Apache virtual host configuration
    print_info "Creating Apache virtual host configuration..."
    cat > /etc/apache2/sites-available/garden-sensors.conf << EOL
<VirtualHost *:80>
    ServerName ${APACHE_DOMAIN}
    DocumentRoot /var/www/html/garden-sensors/public
    
    <Directory /var/www/html/garden-sensors/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/garden-sensors-error.log
    CustomLog \${APACHE_LOG_DIR}/garden-sensors-access.log combined
</VirtualHost>
EOL
    
    # Enable the site
    print_info "Enabling Apache site..."
    a2ensite garden-sensors
    
    # Restart Apache
    print_info "Restarting Apache..."
    systemctl restart apache2
    
    print_status "Apache setup completed"
}

# Main installation process
print_step "Starting installation"

# Run each setup step
validate_env
check_root
install_system_deps
setup_python_env
setup_mysql
setup_application
setup_apache

print_status "Installation completed successfully"
print_info "You can access the application at: http://${APACHE_DOMAIN}" 