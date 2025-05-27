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

# Main cleanup function
cleanup() {
    print_status "Starting dev environment cleanup"
    
    # Clean up Python virtual environment
    print_info "Removing Python virtual environment..."
    rm -rf venv
    
    # Clean up pytest cache
    print_info "Removing pytest cache..."
    rm -rf .pytest_cache
    
    # Clean up PHPUnit cache
    print_info "Removing PHPUnit cache..."
    rm -rf .phpunit.result.cache
    
    print_status "Dev environment cleanup completed successfully"
}

# Execute cleanup
cleanup 