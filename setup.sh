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
    
    # Create database user
    print_info "Creating database user..."
    php tests/setup_db_user.php || print_error "Failed to create database user"
    
    # Create database and tables
    print_info "Creating database and tables..."
    php tests/setup.php || print_error "Failed to setup database"
    
    print_status "MySQL setup completed"
}

# Function to setup application files
setup_app_files() {
    print_step "Setting up application files"
    
    # Create necessary directories
    print_info "Creating application directories..."
    mkdir -p config cache logs || print_error "Failed to create application directories"
    
    # Set proper permissions
    print_info "Setting file permissions..."
    chmod -R 755 . || print_error "Failed to set file permissions"
    chmod -R 777 cache logs || print_error "Failed to set cache and log permissions"
    
    print_status "Application files setup completed"
}

# Main setup process
main() {
    print_step "Starting Garden Sensors Setup"
    
    validate_env
    check_root
    install_system_deps
    setup_python_env
    setup_mysql
    setup_app_files
    
    print_step "Setup Completed Successfully"
    print_info "You can now access the application at http://localhost"
}

# Run main setup process
main 