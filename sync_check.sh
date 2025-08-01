#!/bin/bash

# Garden Sensors Synchronization Check
# This script verifies that the original repo and web deployment are in sync

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

# Function to check file existence and compare
check_file() {
    local file=$1
    local description=$2
    
    if [ -f "/home/cad2040/Code/GardenSensors/$file" ] && [ -f "/var/www/html/garden-sensors/$file" ]; then
        if diff "/home/cad2040/Code/GardenSensors/$file" "/var/www/html/garden-sensors/$file" >/dev/null 2>&1; then
            print_status "✓ $description ($file)"
            return 0
        else
            print_warning "✗ $description ($file) - DIFFERENT"
            return 1
        fi
    else
        print_error "✗ $description ($file) - MISSING"
        return 1
    fi
}

# Main verification
main() {
    print_step "Garden Sensors Synchronization Check"
    
    local sync_ok=true
    
    print_info "Checking key files..."
    
    # Check main scripts
    check_file "setup.sh" "Main setup script" || sync_ok=false
    check_file "cleanup.sh" "Main cleanup script" || sync_ok=false
    check_file "README.md" "Documentation" || sync_ok=false
    check_file "VERSION" "Version file" || sync_ok=false
    check_file "composer.json" "Composer configuration" || sync_ok=false
    check_file "phpunit.xml" "PHPUnit configuration" || sync_ok=false
    check_file ".env.example" "Environment template" || sync_ok=false
    check_file ".env.test" "Test environment" || sync_ok=false
    
    # Check test scripts
    check_file "tests/run_tests.sh" "Test runner" || sync_ok=false
    check_file "tests/check_test_config.php" "Configuration checker" || sync_ok=false
    check_file "tests/run_config_check.sh" "Environment loader" || sync_ok=false
    
    # Check source code
    check_file "src/Config/database.php" "Database configuration" || sync_ok=false
    check_file "src/Core/Database.php" "Database class" || sync_ok=false
    
    print_step "Synchronization Summary"
    
    if [ "$sync_ok" = true ]; then
        print_status "✓ All key files are synchronized between repositories"
        print_info "Original repo: /home/cad2040/Code/GardenSensors"
        print_info "Web deployment: /var/www/html/garden-sensors"
    else
        print_warning "✗ Some files are not synchronized"
        print_info "Please run the sync process to fix differences"
    fi
    
    print_step "Directory Structure"
    echo "Original Repo:"
    ls -la /home/cad2040/Code/GardenSensors/ | grep -E "(setup|clean|VERSION|README)"
    echo -e "\nWeb Deployment:"
    ls -la /var/www/html/garden-sensors/ | grep -E "(setup|clean|VERSION|README)"
}

# Run main function
main 