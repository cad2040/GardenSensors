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

# Function to check if running as root
check_root() {
    if [ "$EUID" -ne 0 ]; then
        print_error "Please run as root (use sudo)"
    fi
}

# Main cleanup function
cleanup() {
    print_status "Starting cleanup"
    
    # Stop Apache service
    print_info "Stopping Apache service..."
    systemctl stop apache2 || print_warning "Failed to stop Apache"
    
    # Remove Apache virtual host
    print_info "Removing Apache virtual host..."
    rm -f /etc/apache2/sites-available/garden-sensors.conf
    rm -f /etc/apache2/sites-enabled/garden-sensors.conf
    
    # Remove application files
    print_info "Removing application files..."
    rm -rf /var/www/garden-sensors
    
    # Remove domain from hosts file
    print_info "Removing domain from hosts file..."
    sed -i '/garden-sensors.local/d' /etc/hosts
    
    # Drop database
    print_info "Dropping database..."
    mysql -e "DROP DATABASE IF EXISTS garden_sensors;"
    mysql -e "DROP USER IF EXISTS 'garden_user'@'localhost';"
    
    # Remove Python virtual environment
    print_info "Removing Python virtual environment..."
    rm -rf venv
    
    # Remove logs
    print_info "Removing log files..."
    rm -f /var/log/apache2/garden-sensors-*.log
    
    # Start Apache service
    print_info "Starting Apache service..."
    systemctl start apache2 || print_warning "Failed to start Apache"
    
    print_status "Cleanup completed successfully"
}

# Check root privileges
check_root

# Execute cleanup
cleanup 