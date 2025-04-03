#!/bin/bash

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
    echo -e "\n${GREEN}========================================${NC}"
    echo -e "${GREEN} STEP: $1 ${NC}"
    echo -e "${GREEN}========================================${NC}\n"
}

# Function to check if a command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
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
        git \
        curl \
        unzip \
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
    
    # Upgrade pip
    print_info "Upgrading pip..."
    $VENV_PYTHON -m pip install --upgrade pip || print_error "Failed to upgrade pip"
    
    # Install Python dependencies
    print_info "Installing Python dependencies from requirements.txt..."
    $VENV_PIP install -r requirements.txt || print_error "Failed to install Python dependencies"
    
    print_status "Python environment setup completed"
}

# Function to setup MySQL database
setup_mysql() {
    print_step "Setting up MySQL database"
    
    # Install MySQL if not installed
    if ! command -v mysql &> /dev/null; then
        print_info "Installing MySQL server..."
        apt-get update
        DEBIAN_FRONTEND=noninteractive apt-get install -y mysql-server
    else
        print_info "MySQL is already installed"
    fi
    
    # Ensure MySQL service is running
    if ! systemctl is-active --quiet mysql; then
        print_info "Starting MySQL service..."
        systemctl start mysql || print_error "Failed to start MySQL service"
        sleep 5  # Wait for MySQL to fully start
    else
        print_info "MySQL service is already running"
    fi
    
    # Secure MySQL installation (if not already done)
    if [ ! -f "/root/.mysql_secure_installation_done" ]; then
        print_info "Securing MySQL installation..."
        
        # Set root password and create .my.cnf
        MYSQL_ROOT_PASSWORD=$(openssl rand -base64 12)
        print_info "Setting MySQL root password..."
        mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '$MYSQL_ROOT_PASSWORD';"
        
        # Create .my.cnf for root
        print_info "Creating MySQL configuration file..."
        cat > /root/.my.cnf << EOL
[client]
user=root
password=$MYSQL_ROOT_PASSWORD
EOL
        chmod 600 /root/.my.cnf
        
        # Remove anonymous users
        print_info "Removing anonymous users..."
        mysql -e "DELETE FROM mysql.user WHERE User='';"
        
        # Remove remote root
        print_info "Removing remote root access..."
        mysql -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');"
        
        # Remove test database
        print_info "Removing test database..."
        mysql -e "DROP DATABASE IF EXISTS test;"
        mysql -e "DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';"
        
        # Reload privileges
        print_info "Reloading privileges..."
        mysql -e "FLUSH PRIVILEGES;"
        
        # Mark as done
        touch /root/.mysql_secure_installation_done
        print_info "MySQL security setup completed"
    else
        print_info "MySQL security setup was already done"
    fi
    
    # Create database and user
    print_info "Creating database and user..."
    mysql -e "CREATE DATABASE IF NOT EXISTS SoilSensors;" || print_error "Failed to create database"
    mysql -e "CREATE USER IF NOT EXISTS 'SoilSensors'@'localhost' IDENTIFIED BY 'SoilSensors123';" || print_error "Failed to create MySQL user"
    mysql -e "GRANT ALL PRIVILEGES ON SoilSensors.* TO 'SoilSensors'@'localhost';" || print_error "Failed to grant privileges"
    mysql -e "FLUSH PRIVILEGES;" || print_error "Failed to flush privileges"
    
    # Import schema if exists
    if [ -f "database/schema.sql" ]; then
        print_info "Importing database schema..."
        mysql < database/schema.sql || print_error "Failed to import database schema"
        print_info "Database schema imported successfully"
    else
        print_warning "Schema file not found. Skipping database import."
    fi
    
    print_status "MySQL setup completed"
    print_info "MySQL root password has been set and saved in /root/.my.cnf"
    print_info "MySQL application user 'SoilSensors' has been created with password 'SoilSensors123'"
}

# Function to setup Apache web server
setup_apache() {
    print_step "Setting up Apache web server"
    
    # Enable required Apache modules
    print_info "Enabling Apache modules..."
    a2enmod rewrite || print_error "Failed to enable rewrite module"
    
    # Find PHP version and enable the correct module
    PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
    print_info "Detected PHP version: $PHP_VERSION"
    a2enmod "php$PHP_VERSION" || print_warning "Failed to enable PHP module $PHP_VERSION, trying default PHP module"
    
    # Create necessary directories
    print_info "Creating web directories..."
    mkdir -p /var/www/html/garden-sensors/cache /var/www/html/garden-sensors/logs || print_error "Failed to create directories"
    
    # Remove existing files if they exist
    if [ -d "/var/www/html/garden-sensors" ]; then
        print_warning "Removing existing files in /var/www/html/garden-sensors"
        rm -rf /var/www/html/garden-sensors/*
    fi
    
    # Copy project files to web directory
    print_info "Copying files to web directory..."
    cp -r GUI/* /var/www/html/garden-sensors/ || print_error "Failed to copy files to web directory"
    
    # Set proper permissions
    print_info "Setting file permissions..."
    sudo chown -R www-data:www-data /var/www/html/garden-sensors/cache /var/www/html/garden-sensors/logs || print_error "Failed to set ownership"
    chmod -R 755 /var/www/html/garden-sensors/ || print_error "Failed to set permissions"
    chmod -R 777 /var/www/html/garden-sensors/cache/ || print_error "Failed to set cache permissions"
    chmod -R 777 /var/www/html/garden-sensors/logs/ || print_error "Failed to set logs permissions"
    
    # Create Apache virtual host configuration
    print_info "Creating Apache virtual host configuration..."
    cat > /etc/apache2/sites-available/garden-sensors.conf << EOL
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
EOL
    
    # Enable the site
    print_info "Enabling Apache site..."
    a2ensite garden-sensors.conf
    
    # Restart Apache
    print_info "Restarting Apache..."
    systemctl restart apache2 || print_error "Failed to restart Apache"
    
    print_status "Apache setup completed"
    print_info "You can access the application at: http://localhost/garden-sensors"
}

# Function to setup configuration files
setup_config() {
    print_step "Setting up configuration files"
    
    # Copy example config if exists
    if [ -f "config.example.php" ]; then
        print_info "Copying example configuration file..."
        cp config.example.php config.php || print_error "Failed to copy config file"
    else
        print_warning "Example config file not found. Creating new config.php..."
        cat > config.php << EOL
<?php
// Database settings
define('DB_HOST', 'localhost');
define('DB_NAME', 'SoilSensors');
define('DB_USER', 'SoilSensors');
define('DB_PASS', 'SoilSensors123');

// FTP settings
define('FTP_HOST', 'your_ftp_host');
define('FTP_USER', 'your_ftp_user');
define('FTP_PASS', 'your_ftp_pass');
define('FTP_PATH', '/public_html/plots/');

// Email settings
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your_email@gmail.com');
define('SMTP_PASS', 'your_app_password');
define('ALERT_EMAIL', 'alerts@yourdomain.com');

// Application settings
define('DATA_RETENTION_DAYS', 30);
define('ALERT_THRESHOLD', 20);
define('REFRESH_INTERVAL', 300);
EOL
    fi
    
    print_status "Configuration setup completed"
}

# Function to setup cron jobs
setup_cron() {
    print_step "Setting up cron jobs"
    
    # Create temporary crontab file
    print_info "Creating temporary crontab file..."
    crontab -l > mycron 2>/dev/null || touch mycron
    
    # Remove existing Garden Sensors cron jobs
    print_info "Removing existing Garden Sensors cron jobs..."
    sed -i '/garden-sensors/d' mycron
    
    # Add cron jobs
    print_info "Adding new cron jobs..."
    echo "*/5 * * * * php /var/www/html/garden-sensors/cron/check_alerts.php" >> mycron
    echo "0 0 * * * php /var/www/html/garden-sensors/cron/cleanup.php" >> mycron
    echo "0 2 * * 0 php /var/www/html/garden-sensors/cron/optimize_db.php" >> mycron
    
    # Install new crontab
    print_info "Installing new crontab..."
    crontab mycron || print_error "Failed to install cron jobs"
    rm mycron
    
    print_status "Cron jobs setup completed"
    print_info "Added the following cron jobs:"
    print_info "  - Check alerts every 5 minutes"
    print_info "  - Clean up old data daily at midnight"
    print_info "  - Optimize database weekly on Sunday at 2 AM"
}

# Function to verify installation
verify_installation() {
    print_step "Verifying installation"
    
    # Check Apache
    print_info "Checking Apache service..."
    if ! systemctl is-active --quiet apache2; then
        print_error "Apache is not running"
    else
        print_info "Apache is running"
    fi
    
    # Check MySQL
    print_info "Checking MySQL service..."
    if ! systemctl is-active --quiet mysql; then
        print_error "MySQL is not running"
    else
        print_info "MySQL is running"
    fi
    
    # Check PHP
    print_info "Checking PHP installation..."
    if ! command_exists php; then
        print_error "PHP is not installed"
    else
        print_info "PHP is installed: $(php -v | head -n 1)"
    fi
    
    # Check database connection
    print_info "Checking database connection..."
    if ! mysql -u SoilSensors -pSoilSensors123 -e "USE SoilSensors;" 2>/dev/null; then
        print_error "Cannot connect to database"
    else
        print_info "Database connection successful"
    fi
    
    # Check web directory
    print_info "Checking web directory..."
    if [ ! -d "/var/www/html/garden-sensors" ]; then
        print_error "Web directory does not exist"
    else
        print_info "Web directory exists"
    fi
    
    # Check file permissions
    print_info "Checking file permissions..."
    if [ ! -w "/var/www/html/garden-sensors/cache" ] || [ ! -w "/var/www/html/garden-sensors/logs" ]; then
        print_warning "Cache or logs directory is not writable"
    else
        print_info "File permissions are correct"
    fi
    
    print_status "Installation verified successfully"
}

# Function to create admin user
create_admin_user() {
    print_step "Creating admin user"
    
    # Generate a random password if not provided
    ADMIN_PASSWORD=${1:-$(openssl rand -base64 8)}
    
    print_info "Creating admin user with username 'admin'..."
    
    # Hash the password
    HASHED_PASSWORD=$(php -r "echo password_hash('$ADMIN_PASSWORD', PASSWORD_DEFAULT);")
    
    # Insert admin user into database
    mysql -u SoilSensors -pSoilSensors123 SoilSensors -e "
    INSERT INTO users (username, password, email, role, created_at) 
    VALUES ('admin', '$HASHED_PASSWORD', 'admin@example.com', 'admin', NOW())
    ON DUPLICATE KEY UPDATE password = '$HASHED_PASSWORD';" || print_warning "Failed to create admin user"
    
    print_status "Admin user created successfully"
    print_info "Admin credentials:"
    print_info "  Username: admin"
    print_info "  Password: $ADMIN_PASSWORD"
}

# Main installation process
main() {
    print_step "Starting Garden Sensors installation"
    
    # Check if running as root
    check_root
    
    # Run installation steps
    install_system_deps
    setup_python_env
    setup_mysql
    setup_apache
    setup_config
    setup_cron
    create_admin_user
    verify_installation
    
    print_step "Installation completed successfully!"
    print_info "Please configure your ESP8266 devices according to the README.md instructions"
    print_info "Default credentials:"
    print_info "MySQL:"
    print_info "  Username: SoilSensors"
    print_info "  Password: SoilSensors123"
    print_info "Web Interface:"
    print_info "  URL: http://localhost/garden-sensors"
    print_info "  Username: admin"
    print_info "  Password: (see above)"
}

# Run main function
main 