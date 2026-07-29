#!/bin/bash

# Garden Sensors Cleanup Script
# This script provides different cleanup options for the Garden Sensors project

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Colo

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

# Function to print step heade
print_step() {
    echo -e "\n========================================"
    echo " STEP: $1 "
    echo "========================================\n"
}

# Function to show usage
show_usage() {
    echo "Usage: $0 [OPTION]"
    echo ""
    echo "Options:"
    echo "  production    Full production cleanup (requires root)"
    echo "  test          Test environment cleanup (requires root)"
    echo "  local         Local development cleanup (Apache/MySQL host deploy)"
    echo "  docker        Stop containers and remove Docker volumes"
    echo "  help          Show this help message"
    echo ""
    echo "Examples:"
    echo "  sudo $0 production    # Full production cleanup"
    echo "  sudo $0 test          # Test environment cleanup"
    echo "  sudo $0 local         # Local bare-metal cleanup"
    echo "  $0 docker             # Docker Compose cleanup (down -v)"
    echo ""
    echo "Non-interactive: CONFIRM=y $0 local"
}

# Function to check if running as root
check_root() {
    if [ "$EUID" -ne 0 ]; then
        print_error "This cleanup requires root privileges (use sudo)"
    fi
    print_status "Root privileges confirmed"
}

# Function to get user confirmation
get_confirmation() {
    local message=$1
    if [ "${CONFIRM:-}" = "y" ] || [ "${CONFIRM:-}" = "Y" ]; then
        print_info "Confirmation skipped (CONFIRM=y)"
        return 0
    fi
    echo -e "${YELLOW}$message${NC}"
    read -r -n 1 -p "Do you want to proceed? (y/N) " REPLY
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        print_error "Operation cancelled by user"
    fi
}

compose_cmd() {
    if docker compose version >/dev/null 2>&1; then
        docker compose "$@"
    elif command -v docker-compose >/dev/null 2>&1; then
        docker-compose "$@"
    else
        print_error "Neither 'docker compose' nor 'docker-compose' is available"
    fi
}

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Function to check MySQL connectivity
check_mysql_connection() {
    if mysql -u root -pnewrootpassword -e "SELECT 1;" 2>/dev/null; then
        return 0
    else
        return 1
    fi
}

# Function to backup databases before cleanup
backup_databases() {
    local mode=$1
    local backup_dir="/tmp/garden-sensors-backup-$(date +%Y%m%d_%H%M%S)"
    
    print_info "Checking MySQL connectivity..."
    if ! check_mysql_connection; then
        print_warning "MySQL is not accessible. Skipping database backup."
        return
    fi
    
    print_info "Creating backup directory: $backup_dir"
    mkdir -p "$backup_dir" || print_warning "Failed to create backup directory"
    
    case $mode in
        "production")
            if mysql -u root -pnewrootpassword -e "USE garden_sensors;" 2>/dev/null; then
                print_info "Backing up production database..."
                mysqldump -u root -pnewrootpassword garden_sensors > "$backup_dir/garden_sensors.sql" || print_warning "Failed to backup production database"
            fi
            ;;
        "test")
            if mysql -u root -pnewrootpassword -e "USE garden_sensors;" 2>/dev/null; then
                print_info "Backing up production database (used for tests)..."
                mysqldump -u root -pnewrootpassword garden_sensors > "$backup_dir/garden_sensors.sql" || print_warning "Failed to backup production database"
            fi
            ;;
        "local")
            if mysql -u root -pnewrootpassword -e "USE garden_sensors;" 2>/dev/null; then
                print_info "Backing up local production database..."
                mysqldump -u root -pnewrootpassword garden_sensors > "$backup_dir/garden_sensors.sql" || print_warning "Failed to backup local production database"
            fi
            ;;
    esac
    
    if [ -f "$backup_dir/garden_sensors.sql" ]; then
        print_status "Database backup completed: $backup_dir"
        print_info "Backup files:"
        ls -la "$backup_dir"/
    else
        print_info "No databases to backup"
        rmdir "$backup_dir" 2>/dev/null
    fi
}

# Function to cleanup databases
cleanup_databases() {
    local mode=$1
    
    print_info "Checking MySQL connectivity..."
    if ! check_mysql_connection; then
        print_warning "MySQL is not accessible. Skipping database cleanup."
        return
    fi
    
    case $mode in
        "production")
            print_info "Cleaning up production database..."
            
            # Check if production database exists
            if mysql -u root -pnewrootpassword -e "USE garden_sensors;" 2>/dev/null; then
                print_info "Dropping production database 'garden_sensors'..."
                mysql -u root -pnewrootpassword -e "DROP DATABASE IF EXISTS garden_sensors;" || print_warning "Failed to drop production database"
            else
                print_info "Production database 'garden_sensors' does not exist"
            fi
            
            # Remove garden_user if it exists
            print_info "Checking for application user..."
            if mysql -u root -pnewrootpassword -e "SELECT User FROM mysql.user WHERE User='garden_user';" 2>/dev/null | grep -q "garden_user"; then
                print_info "Removing application user 'garden_user'..."
                mysql -u root -pnewrootpassword -e "DROP USER IF EXISTS 'garden_user'@'localhost';" || print_warning "Failed to remove application user"
            fi
            
            # Remove garden_sensors user if it exists
            print_info "Checking for garden_sensors user..."
            if mysql -u root -pnewrootpassword -e "SELECT User FROM mysql.user WHERE User='garden_sensors';" 2>/dev/null | grep -q "garden_sensors"; then
                print_info "Removing garden_sensors user..."
                mysql -u root -pnewrootpassword -e "DROP USER IF EXISTS 'garden_sensors'@'localhost';" || print_warning "Failed to remove garden_sensors user"
            fi
            ;;
            
        "test")
            print_info "Cleaning up production database (used for tests)..."
            
            # Check if production database exists
            if mysql -u root -pnewrootpassword -e "USE garden_sensors;" 2>/dev/null; then
                print_info "Dropping production database 'garden_sensors'..."
                mysql -u root -pnewrootpassword -e "DROP DATABASE IF EXISTS garden_sensors;" || print_warning "Failed to drop production database"
            else
                print_info "Production database 'garden_sensors' does not exist"
            fi
            ;;
            
        "local")
            print_info "Cleaning up local databases..."
            
            # Drop both databases if they exist (removes seed plants/sensors/readings + plant_catalog)
            if mysql -u root -pnewrootpassword -e "USE garden_sensors;" 2>/dev/null; then
                print_info "Dropping local production database (includes demo seed data and plant_catalog)..."
                mysql -u root -pnewrootpassword -e "DROP DATABASE IF EXISTS garden_sensors;" || print_warning "Failed to drop local production database"
            fi
            
            # Note: Tests now use production database, so no separate test database cleanup needed
            ;;
    esac
    
    print_status "Database cleanup completed for $mode mode"
}

# Function to cleanup database-related files
cleanup_database_files() {
    print_info "Cleaning up database-related files..."
    
    # Remove database configuration files
    if [ -f ".env" ]; then
        print_info "Removing .env file..."
        rm -f .env || print_warning "Failed to remove .env file"
    fi
    
    if [ -f ".env.test" ]; then
        print_info "Removing .env.test file..."
        rm -f .env.test || print_warning "Failed to remove .env.test file"
    fi
    
    # Remove database backup files older than 7 days
    print_info "Cleaning up old database backups..."
    find /tmp -name "garden-sensors-backup-*" -type d -mtime +7 -exec rm -rf {} \; 2>/dev/null || print_warning "Failed to clean up old backups"
    
    # Remove database log files
    print_info "Cleaning up database log files..."
    find . -name "*.log" -type f -delete 2>/dev/null || print_warning "Failed to clean up log files"
    
    print_status "Database file cleanup completed"
}

# Function to cleanup production deployment
cleanup_production() {
    print_step "Starting Production Cleanup"
    
    check_root
    get_confirmation "This will remove all garden-sensors production components. Are you sure?"
    
    # Backup databases before cleanup
    print_info "Creating database backup before cleanup..."
    backup_databases "production"
    
    # Stop services
    print_info "Stopping services..."
    systemctl stop apache2 || print_warning "Failed to stop Apache"
    systemctl stop mysql || print_warning "Failed to stop MySQL"
    
    # Remove web deployment
    if [ -d "/var/www/html/garden-sensors" ]; then
        print_info "Removing web deployment..."
        rm -rf /var/www/html/garden-sensors || print_warning "Failed to remove web deployment"
    fi
    
    # Remove Apache configuration
    print_info "Removing Apache configuration..."
    a2dissite garden-sensors.conf 2>/dev/null || print_warning "Failed to disable site"
    rm -f /etc/apache2/sites-available/garden-sensors.conf || print_warning "Failed to remove site config"
    
    # Remove logs
    print_info "Removing logs..."
    rm -f /var/log/apache2/garden-sensors-*.log || print_warning "Failed to remove logs"
    
    # Cleanup databases
    cleanup_databases "production"
    
    # Cleanup database files
    cleanup_database_files
    
    # Restart Apache
    print_info "Restarting Apache..."
    systemctl restart apache2 || print_warning "Failed to restart Apache"
    
    print_status "Production cleanup completed"
}

# Function to cleanup test environment
cleanup_test() {
    print_step "Starting Test Environment Cleanup"
    
    check_root
    get_confirmation "This will remove the test environment deployment. Are you sure?"
    
    # Backup databases before cleanup
    print_info "Creating database backup before cleanup..."
    backup_databases "test"
    
    # Remove web deployment
    if [ -d "/var/www/html/garden-sensors" ]; then
        print_info "Removing test web deployment..."
        rm -rf /var/www/html/garden-sensors || print_warning "Failed to remove test deployment"
    fi
    
    # Cleanup databases
    cleanup_databases "test"
    
    # Cleanup database files
    cleanup_database_files
    
    # Remove Apache configuration if it exists
    if [ -f "/etc/apache2/sites-available/garden-sensors.conf" ]; then
        print_info "Removing Apache configuration..."
        a2dissite garden-sensors.conf 2>/dev/null || print_warning "Failed to disable site"
        rm -f /etc/apache2/sites-available/garden-sensors.conf || print_warning "Failed to remove site config"
        systemctl restart apache2 || print_warning "Failed to restart Apache"
    fi
    
    print_status "Test environment cleanup completed"
}

# Function to cleanup local development
cleanup_local() {
    print_step "Starting Local Development Cleanup"
    
    get_confirmation "This will remove local bare-metal development files and host deploy. Are you sure?"
    
    cd "$PROJECT_ROOT" || print_error "Failed to change to project root"

    # Backup databases before cleanup
    print_info "Creating database backup before cleanup..."
    backup_databases "local"
    
    # Remove Apache site + web root when present (local setup often used sudo)
    if [ -f "/etc/apache2/sites-available/garden-sensors.conf" ] || [ -d "/var/www/html/garden-sensors" ]; then
        if [ "$EUID" -eq 0 ] || sudo -n true 2>/dev/null; then
            print_info "Removing Apache site and web deployment..."
            sudo a2dissite garden-sensors.conf 2>/dev/null || a2dissite garden-sensors.conf 2>/dev/null || true
            sudo rm -f /etc/apache2/sites-available/garden-sensors.conf 2>/dev/null || rm -f /etc/apache2/sites-available/garden-sensors.conf 2>/dev/null || true
            sudo rm -rf /var/www/html/garden-sensors 2>/dev/null || rm -rf /var/www/html/garden-sensors 2>/dev/null || true
            sudo rm -f /var/log/apache2/garden-sensors*.log 2>/dev/null || true
            sudo systemctl reload apache2 2>/dev/null || systemctl reload apache2 2>/dev/null || true
        else
            print_warning "Apache/web root present but no sudo; skipping host web cleanup"
        fi
    fi

    # Clean up Python virtual environment
    print_info "Removing Python virtual environment..."
    rm -rf venv || print_warning "Failed to remove virtual environment"
    rm -rf .venv || print_warning "Failed to remove .venv virtual environment"
    
    # Clean up pytest cache
    print_info "Removing pytest cache..."
    rm -rf .pytest_cache || print_warning "Failed to remove pytest cache"
    
    # Clean up PHPUnit cache
    print_info "Removing PHPUnit cache..."
    rm -rf .phpunit.result.cache || print_warning "Failed to remove PHPUnit cache"
    
    # Clean up vendor directory
    print_info "Removing Composer dependencies..."
    rm -rf vendor || print_warning "Failed to remove vendor directory"
    
    # Clean up environment files
    print_info "Removing environment files..."
    rm -f .env .env.test || print_warning "Failed to remove environment files"
    
    # Cleanup databases
    cleanup_databases "local"
    
    # Cleanup database files
    cleanup_database_files
    
    print_status "Local development cleanup completed"
}

# Function to cleanup Docker Compose stack
cleanup_docker() {
    print_step "Starting Docker Compose Cleanup"
    
    get_confirmation "This will stop containers and remove Docker volumes (DB data wiped). Are you sure?"
    
    cd "$PROJECT_ROOT" || print_error "Failed to change to project root"

    if [ ! -f "$PROJECT_ROOT/docker-compose.yml" ]; then
        print_error "docker-compose.yml not found in $PROJECT_ROOT"
    fi

    print_info "Stopping containers and removing volumes..."
    # Prefer sudo when the daemon requires it (common on this host)
    if sudo -n docker info >/dev/null 2>&1; then
        if sudo docker compose version >/dev/null 2>&1; then
            sudo docker compose -f "$PROJECT_ROOT/docker-compose.yml" down -v --remove-orphans \
                || print_warning "docker compose down reported errors"
        else
            sudo docker-compose -f "$PROJECT_ROOT/docker-compose.yml" down -v --remove-orphans \
                || print_warning "docker-compose down reported errors"
        fi
    else
        compose_cmd -f "$PROJECT_ROOT/docker-compose.yml" down -v --remove-orphans \
            || print_warning "compose down reported errors"
    fi

    print_info "Removing optional .env.docker (example file kept)..."
    rm -f "$PROJECT_ROOT/.env.docker" || true

    print_status "Docker Compose cleanup completed"
    print_info "Images are retained. Remove with: docker image prune / docker rmi gardensensors_web"
}

# Main script
case "${1:-help}" in
    production)
        cleanup_production
        ;;
    test)
        cleanup_test
        ;;
    local)
        cleanup_local
        ;;
    docker)
        cleanup_docker
        ;;
    help|--help|-h)
        show_usage
        ;;
    *)
        print_error "Unknown option: $1"
        show_usage
        ;;
esac 