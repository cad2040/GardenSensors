#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Backup directory
BACKUP_DIR="/var/backups/garden-sensors"

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

# Function to verify backup
verify_backup() {
    print_info "Verifying backup..."
    
    # Check if backup exists
    if [ ! -d "$BACKUP_DIR" ]; then
        print_warning "No backup directory found"
        return 1
    fi
    
    # Check backup integrity
    if [ -f "$BACKUP_DIR/latest_backup.tar.gz" ]; then
        if ! tar -tzf "$BACKUP_DIR/latest_backup.tar.gz" >/dev/null 2>&1; then
            print_warning "Backup file is corrupted"
            return 1
        fi
        print_status "Backup verification successful"
        return 0
    else
        print_warning "No backup file found"
        return 1
    fi
}

# Function to get user confirmation
get_confirmation() {
    local message=$1
    echo -e "${YELLOW}$message${NC}"
    read -p "Do you want to proceed? (y/N) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        print_error "Operation cancelled by user"
    fi
}

# Main cleanup function
cleanup() {
    print_status "Starting cleanup"
    
    # Verify backup before proceeding
    verify_backup
    
    # Get user confirmation
    get_confirmation "This will remove all garden-sensors components. Are you sure you want to proceed?"
    
    # Stop services
    print_info "Stopping services..."
    systemctl stop apache2 || print_warning "Failed to stop Apache"
    systemctl stop mysql || print_warning "Failed to stop MySQL"
    systemctl stop prometheus || print_warning "Failed to stop Prometheus"
    systemctl stop apache-exporter || print_warning "Failed to stop Apache exporter"
    systemctl stop mysql-exporter || print_warning "Failed to stop MySQL exporter"
    
    # Clear PHP application caches
    if [ -d "/var/www/garden-sensors" ]; then
        print_info "Clearing PHP application caches..."
        cd /var/www/garden-sensors
        if [ -f "artisan" ]; then
            php artisan cache:clear
            php artisan config:clear
            php artisan route:clear
            php artisan view:clear
        fi
    fi
    
    # Remove Apache virtual host
    print_info "Removing Apache virtual host..."
    rm -f /etc/apache2/sites-available/garden-sensors.conf
    rm -f /etc/apache2/sites-enabled/garden-sensors.conf
    rm -f /etc/apache2/conf-available/rate-limit.conf
    
    # Remove application files
    print_info "Removing application files..."
    rm -rf /var/www/garden-sensors
    
    # Remove domain from hosts file
    print_info "Removing domain from hosts file..."
    sed -i '/garden-sensors.local/d' /etc/hosts
    
    # Drop database and user
    print_info "Dropping database..."
    mysql -e "DROP DATABASE IF EXISTS garden_sensors;"
    mysql -e "DROP USER IF EXISTS 'garden_user'@'localhost';"
    
    # Remove Python virtual environment
    print_info "Removing Python virtual environment..."
    rm -rf venv
    
    # Remove logs
    print_info "Removing log files..."
    rm -f /var/log/apache2/garden-sensors-*.log
    
    # Remove Composer cache
    print_info "Removing Composer cache..."
    rm -rf ~/.composer/cache/*
    
    # Remove monitoring components
    print_info "Removing monitoring components..."
    systemctl disable prometheus apache-exporter mysql-exporter
    rm -f /etc/systemd/system/apache-exporter.service
    rm -f /etc/systemd/system/mysql-exporter.service
    rm -f /usr/local/bin/apache_exporter
    rm -f /usr/local/bin/mysqld_exporter
    apt-get remove -y prometheus prometheus-node-exporter || print_warning "Failed to remove monitoring packages"
    
    # Remove SSL certificate
    print_info "Removing SSL certificate..."
    certbot delete --cert-name garden-sensors.local --non-interactive || print_warning "Failed to remove SSL certificate"
    
    # Remove firewall rules
    print_info "Removing firewall rules..."
    ufw delete allow http || print_warning "Failed to remove HTTP rule"
    ufw delete allow https || print_warning "Failed to remove HTTPS rule"
    
    # Remove any remaining temporary files
    print_info "Removing temporary files..."
    rm -f composer-setup.php
    rm -f composer.phar
    
    # Start services
    print_info "Starting services..."
    systemctl start mysql || print_warning "Failed to start MySQL"
    systemctl start apache2 || print_warning "Failed to start Apache"
    
    print_status "Cleanup completed successfully"
}

# Check root privileges
check_root

# Execute cleanup
cleanup 