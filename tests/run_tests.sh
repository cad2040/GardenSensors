#!/bin/bash

# Garden Sensors Test Runner
# This script runs all tests for the project

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color
PYTHON_VENV_PATH=""

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

# Function to detect Python virtual environment path
detect_python_venv() {
    if [ -n "$PYTHON_VENV" ] && [ -f "$PYTHON_VENV/bin/activate" ]; then
        PYTHON_VENV_PATH="$PYTHON_VENV"
        return 0
    fi

    if [ -f ".venv/bin/activate" ]; then
        PYTHON_VENV_PATH=".venv"
        return 0
    fi

    if [ -f "venv/bin/activate" ]; then
        PYTHON_VENV_PATH="venv"
        return 0
    fi

    return 1
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
    if detect_python_venv; then
        print_info "Using Python venv at: $PYTHON_VENV_PATH"
        source "$PYTHON_VENV_PATH/bin/activate"
    else
        print_error "Virtual environment not found. Set PYTHON_VENV, or create .venv/ or venv/."
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

# Function to run GUI tests
run_gui_tests() {
    print_step "Running GUI Tests"

    if ! detect_python_venv; then
        print_warning "Virtual environment not found. Skipping GUI tests."
        return 0
    fi

    print_info "Using Python venv at: $PYTHON_VENV_PATH"
    source "$PYTHON_VENV_PATH/bin/activate"
    if ! python -c "import playwright" >/dev/null 2>&1; then
        print_warning "Python playwright package not installed. Run 'pip install -r requirements.txt'. Skipping GUI tests."
        return 0
    fi

    if [ ! -d "$HOME/.cache/ms-playwright" ]; then
        print_info "Installing Playwright browser (Chromium)..."
        python -m playwright install chromium
        if [ $? -ne 0 ]; then
            print_warning "Playwright browser install failed. Skipping GUI tests."
            return 0
        fi
    fi

    print_info "Starting local PHP server for GUI tests..."
    php -S 127.0.0.1:8000 -t public > /tmp/garden-sensors-gui.log 2>&1 &
    GUI_SERVER_PID=$!
    sleep 2

    print_info "Running Playwright GUI suite..."
    GUI_BASE_URL=http://127.0.0.1:8000 pytest tests/python/test_gui_pages.py
    GUI_EXIT_CODE=$?

    kill "$GUI_SERVER_PID" 2>/dev/null
    wait "$GUI_SERVER_PID" 2>/dev/null

    if [ $GUI_EXIT_CODE -eq 0 ]; then
        print_status "GUI tests passed"
    else
        print_warning "GUI tests failed"
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

    # Run GUI tests
    run_gui_tests
    
    print_step "Test Suite Completed"
    print_status "All tests completed. Check output above for results."
}

# Run main function
main 