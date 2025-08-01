#!/bin/bash

# Garden Sensors Test Runner
# This script runs all tests for the project

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

# Function to load environment variables
load_env() {
    # Load environment variables from .env.test
    if [ -f .env.test ]; then
        export $(cat .env.test | grep -v '^#' | xargs)
    fi
    
    # Set testing environment variable
    export TESTING=true
}

# Function to run PHP tests
run_php_tests() {
    print_step "Running PHP Tests"
    
    print_info "Running PHPUnit tests..."
    if [ -f "./vendor/bin/phpunit" ]; then
        ./vendor/bin/phpunit
        if [ $? -eq 0 ]; then
            print_status "PHP tests passed"
        else
            print_warning "PHP tests failed"
            return 1
        fi
    else
        print_error "PHPUnit not found. Please run 'composer install' first."
        return 1
    fi
}

# Function to run Python tests
run_python_tests() {
    print_step "Running Python Tests"
    
    print_info "Activating virtual environment..."
    if [ -f "venv/bin/activate" ]; then
        source venv/bin/activate
    else
        print_error "Virtual environment not found. Please run setup first."
        return 1
    fi
    
    print_info "Running pytest..."
    if command -v pytest &> /dev/null; then
        pytest
        if [ $? -eq 0 ]; then
            print_status "Python tests passed"
        else
            print_warning "Python tests failed"
            return 1
        fi
    else
        print_error "pytest not found. Please install Python dependencies first."
        return 1
    fi
}

# Function to run configuration check
run_config_check() {
    print_step "Running Configuration Check"
    
    if [ -f "tests/check_test_config.php" ]; then
        php tests/check_test_config.php
        if [ $? -eq 0 ]; then
            print_status "Configuration check passed"
        else
            print_warning "Configuration check failed"
            return 1
        fi
    else
        print_error "Configuration check script not found"
        return 1
    fi
}

# Main function
main() {
    print_step "Starting Garden Sensors Test Suite"
    
    # Load environment variables
    load_env
    
    # Run configuration check
    run_config_check
    
    # Run PHP tests
    run_php_tests
    
    # Run Python tests
    run_python_tests
    
    print_step "Test Suite Completed"
    print_status "All tests completed. Check output above for results."
}

# Run main function
main 