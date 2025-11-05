# Garden Sensors Application

A comprehensive garden monitoring system that tracks soil moisture, temperature, and other environmental factors using Arduino sensors and provides a web interface for data visualization and management.

## Features

- Real-time soil moisture and temperature monitoring
- **Interactive plant-based plots** - Bokeh-powered visualizations with filtering
- Web-based dashboard for data visualization
- Plant management with moisture threshold tracking
- Sensor-plant linking for automated watering
- Email alerts for low moisture levels
- Data export functionality
- User authentication and role-based access control
- Secure HTTPS access
- Rate limiting for API endpoints
- Automated backups with rotation
- System monitoring with Prometheus
- Firewall protection with UFW

## System Requirements

- Ubuntu 20.04 or later
- Python 3.8 or later
- MySQL 8.0 or later
- Apache 2.4 or later
- PHP 7.4 or later
- Arduino (for sensor deployment)

## Quick Start

### Option 1: Production Setup (Recommended for Production)

```bash
# Clone the repository
git clone https://github.com/yourusername/garden-sensors.git
cd garden-sensors

# Set up environment variables
cp .env.example .env
# Edit .env with your configuration

# Run production setup (requires root)
sudo ./setup.sh production
```

### Option 2: Test Environment Setup (Recommended for Testing)

```bash
# Clone the repository
git clone https://github.com/yourusername/garden-sensors.git
cd garden-sensors

# Set up environment variables
cp .env.example .env
cp .env.example .env.test
# Edit both files with your configuration

# Run test environment setup (requires root)
sudo ./setup.sh test
```

### Option 3: Local Development Setup (With Web UI and Testing)

```bash
# Clone the repository
git clone https://github.com/yourusername/garden-sensors.git
cd garden-sensors

# Set up environment variables
cp .env.example .env
cp .env.example .env.test
# Edit both files with your configuration

# Run local development setup
sudo ./setup.sh local
```

**Note:** The local setup now includes:
- Full web deployment with Apache
- Automatic test execution (PHP + Python)
- Test data cleanup after successful tests
- Production-ready database after deployment

## Setup Options

The main setup script (`setup.sh`) provides three different setup options:

### Production Setup (`sudo ./setup.sh production`)
- Installs all system dependencies (Apache, MySQL, PHP, Python)
- Deploys application to `/var/www/html/garden-sensors`
- Configures Apache virtual host
- Sets up production database
- Configures file permissions for web access
- **Use for**: Production servers, full deployment

### Test Environment Setup (`sudo ./setup.sh test`)
- Deploys application to web root for UI testing
- Installs PHP and Python dependencies
- Sets up test database
- Configures Apache for web access
- Runs unit tests automatically
- **Use for**: Testing with web UI, debugging

### Local Development Setup (`sudo ./setup.sh local`)
- Installs PHP and Python dependencies
- Sets up local database
- Deploys to web root with Apache
- **Runs full test suite automatically** (PHP + Python)
- **Cleans up test data** after successful tests
- **Use for**: Development, testing with web UI, production-ready deployments

## Testing

### Automated Testing During Deployment

The deployment process (`setup.sh local` and `setup.sh production`) now automatically:
1. **Runs full test suite** after deployment completes
   - PHP unit tests (126 tests, 253 assertions)
   - Python unit tests (35 tests)
2. **Validates deployment** - Deployment fails if tests fail
3. **Cleans up test data** - Removes all test users, sensors, plants, and related data
4. **Verifies cleanup** - Confirms database is clean and production-ready

### Manual Test Execution

1. **PHP Tests:**
```bash
# From project root or web root
./vendor/bin/phpunit --testdox
```

2. **Python Tests:**
```bash
# Activate virtual environment first
source venv/bin/activate
python -m pytest tests/python/ -v
```

3. **Configuration Check:**
```bash
# Verify test environment setup
./tests/run_config_check.sh
```

### Test Data Cleanup

After running tests, you can manually clean up test data:
```bash
# From deployment directory
mysql -u root -pnewrootpassword garden_sensors < database/cleanup_test_data.sql
```

The cleanup script removes:
- Test users (`testuser_*`, `test_*@example.com`)
- Test sensors (names containing "Test")
- Test plants (names starting with "Test Plant")
- Orphaned readings, plant_sensors, and pins

### Test Environment Setup

The project includes comprehensive test setup:
- Separate test database (`garden_sensors_test`)
- Test environment variables (`.env.test`)
- Automated test data generation
- Coverage reporting
- Automatic cleanup after tests

## Cleanup

The cleanup script (`cleanup.sh`) provides comprehensive cleanup options with automatic database backup:

### Production Cleanup (`sudo ./cleanup.sh production`)
- **Database Backup**: Automatically creates backup before cleanup
- **Database Cleanup**: Drops production database and removes application user
- **Web Deployment**: Removes web deployment from `/var/www/html/garden-sensors`
- **Apache Configuration**: Removes Apache virtual host configuration
- **Services**: Stops and restarts Apache/MySQL services
- **Logs**: Removes application log files

### Test Environment Cleanup (`sudo ./cleanup.sh test`)
- **Database Backup**: Automatically creates backup before cleanup
- **Database Cleanup**: Drops test database
- **Web Deployment**: Removes test web deployment
- **Apache Configuration**: Removes Apache configuration if present
- **File Cleanup**: Removes environment files and logs

### Local Development Cleanup (`./cleanup.sh local`)
- **Database Backup**: Automatically creates backup before cleanup
- **Database Cleanup**: Drops both local production and test databases
- **Python Environment**: Removes Python virtual environment
- **PHP Dependencies**: Removes Composer dependencies (`vendor/`)
- **Cache Cleanup**: Removes pytest and PHPUnit caches
- **Environment Files**: Removes `.env` and `.env.test` files
- **Log Files**: Removes application log files

### Database Backup and Recovery

The cleanup script automatically creates backups before removing databases:

```bash
# Backup location
/tmp/garden-sensors-backup-YYYYMMDD_HHMMSS/

# Restore from backup
mysql -u root -p garden_sensors < /tmp/garden-sensors-backup-YYYYMMDD_HHMMSS/garden_sensors.sql
mysql -u root -p garden_sensors_test < /tmp/garden-sensors-backup-YYYYMMDD_HHMMSS/garden_sensors_test.sql
```

### Cleanup Safety Features

- **User Confirmation**: All cleanup operations require user confirmation
- **Automatic Backup**: Databases are backed up before deletion
- **Existence Checks**: Scripts check if components exist before removal
- **Error Handling**: Graceful handling of missing components
- **Backup Retention**: Old backups are automatically cleaned up after 7 days

## Database Setup

### Database Requirements

The application requires MySQL 8.0 or later with the following databases:
- **Production Database**: `garden_sensors`
- **Test Database**: `garden_sensors_test`

### Database Deployment Steps

#### 1. MySQL Installation and Configuration

```bash
# Install MySQL Server
sudo apt update
sudo apt install mysql-server

# Secure MySQL installation
sudo mysql_secure_installation

# Access MySQL as root
sudo mysql -u root -p
```

#### 2. Create Database and User

```sql
-- Create production database
CREATE DATABASE garden_sensors CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create test database
CREATE DATABASE garden_sensors_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create application user (optional, for production)
CREATE USER 'garden_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON garden_sensors.* TO 'garden_user'@'localhost';
GRANT ALL PRIVILEGES ON garden_sensors_test.* TO 'garden_user'@'localhost';
FLUSH PRIVILEGES;
```

#### 3. Database Schema Deployment

The setup script automatically deploys the database schema:

```bash
# For production setup
sudo ./setup.sh production

# For test setup
sudo ./setup.sh test

# For local development
./setup.sh local
```

#### 4. Manual Schema Deployment (if needed)

```bash
# Deploy production schema
mysql -u root -p garden_sensors < database/schema.sql

# Deploy test schema
mysql -u root -p garden_sensors_test < tests/database.sql
```

### Database Tables

The application creates the following tables:

| Table | Purpose | Key Fields |
|-------|---------|------------|
| `users` | User authentication and management | id, username, email, password_hash |
| `sensors` | Sensor device information | id, name, type, location, status |
| `readings` | Sensor data readings | id, sensor_id, reading_value, reading_timestamp |
| `plants` | Plant information and management | id, name, species, location, user_id, min_soil_moisture, max_soil_moisture |
| `plant_sensors` | Plant-sensor relationships | id, plant_id, sensor_id, last_watered, next_watering, water_amount |
| `pins` | GPIO pin assignments | id, pin_number, type, sensor_id |
| `notifications` | User notifications | id, user_id, type, message, created_at |
| `settings` | Application settings | id, key, value, updated_at |

**Note:** The `fact_plants` table has been removed in favor of `plant_sensors` which provides better structure and naming conventions.

### Database Configuration

#### Environment Variables

Create `.env` and `.env.test` files with the following variables:

```bash
# Database Configuration
DB_HOST=localhost
DB_USER=root                    # Or 'garden_user' for production
DB_PASS=newrootpassword         # Your MySQL root password
DB_NAME=garden_sensors          # Production database name
DB_DATABASE=garden_sensors_test # Test database name
DB_PORT=3306

# Application Configuration
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# Testing
TESTING=false  # Set to true for test environment
DB_CONNECTION=mysql
```

#### Database Connection Settings

The application supports both PHP and Python database connections:

**PHP Connection** (`src/Core/Database.php`):
- Uses PDO for database operations
- Supports transactions and prepared statements
- Automatic connection pooling

**Python Connection** (`python/DBConnect.py`):
- Uses `mysql.connector` for database operations
- Includes retry logic for connection failures
- Supports pandas DataFrames for data analysis

### Database Backup and Recovery

#### Automated Backups

```bash
# Create backup directory
sudo mkdir -p /var/backups/garden-sensors

# Create backup script
sudo nano /usr/local/bin/backup-garden-sensors.sh
```

Backup script content:
```bash
#!/bin/bash
BACKUP_DIR="/var/backups/garden-sensors"
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u root -pnewrootpassword garden_sensors > $BACKUP_DIR/garden_sensors_$DATE.sql
find $BACKUP_DIR -name "*.sql" -mtime +7 -delete
```

#### Manual Backup

```bash
# Backup production database
mysqldump -u root -p garden_sensors > garden_sensors_backup.sql

# Backup test database
mysqldump -u root -p garden_sensors_test > garden_sensors_test_backup.sql
```

#### Database Recovery

```bash
# Restore production database
mysql -u root -p garden_sensors < garden_sensors_backup.sql

# Restore test database
mysql -u root -p garden_sensors_test < garden_sensors_test_backup.sql
```

### Database Monitoring

#### Performance Monitoring

```sql
-- Check database size
SELECT 
    table_schema AS 'Database',
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)'
FROM information_schema.tables 
WHERE table_schema IN ('garden_sensors', 'garden_sensors_test')
GROUP BY table_schema;

-- Check table sizes
SELECT 
    table_name AS 'Table',
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.tables 
WHERE table_schema = 'garden_sensors'
ORDER BY (data_length + index_length) DESC;
```

#### Connection Monitoring

```sql
-- Check active connections
SHOW PROCESSLIST;

-- Check connection count
SELECT COUNT(*) as active_connections FROM information_schema.processlist;
```

## Security Features

- SSL/TLS encryption for web access
- UFW firewall with minimal open ports
- Rate limiting for API endpoints
- Secure password storage
- Regular security updates
- Automated backup system
- Monitoring and alerting

## Monitoring

The application includes comprehensive monitoring:
- System metrics (CPU, memory, disk usage)
- Apache server metrics
- MySQL database metrics
- Application-specific metrics
- Prometheus dashboard for visualization

## Backup System

Automated backups include:
- Database dumps
- Application files
- Configuration files
- 7-day retention period
- Daily rotation

## Development

### Arduino Development

To enable Arduino development:
```bash
export ARDUINO_DEVELOPMENT=true
sudo ./setup.sh production
```

### Local Development

1. Create a virtual environment:
```bash
python3 -m venv venv
source venv/bin/activate
```

2. Install dependencies:
```bash
pip install -r requirements.txt
```

3. Set up development database:
```bash
mysql -u root -p < database/schema.sql
```

4. Set up test database:
```bash
./tests/setup_test_db.sh
```

## Maintenance

### Backup Management

Backups are stored in `/var/backups/garden-sensors` and automatically rotated daily.

### Log Management

Logs are stored in:
- Apache logs: `/var/log/apache2/garden-sensors-*.log`
- Application logs: `/var/www/garden-sensors/storage/logs`
- Monitoring logs: `/var/log/prometheus`

### Monitoring Access

Access the monitoring dashboard:
```bash
# Prometheus
http://localhost:9090

# Node Exporter
http://localhost:9100/metrics

# Apache Exporter
http://localhost:9117/metrics

# MySQL Exporter
http://localhost:9104/metrics
```

## Troubleshooting

### Common Issues

1. Permission Issues
```bash
sudo chown -R www-data:www-data /var/www/html/garden-sensors
sudo chmod -R 755 /var/www/html/garden-sensors
```

2. Database Connection
```bash
sudo mysql -u root -p
```

3. Apache Configuration
```bash
sudo apache2ctl configtest
sudo systemctl restart apache2
```

### Logs

Check relevant logs for issues:
```bash
# Apache error log
sudo tail -f /var/log/apache2/garden-sensors-error.log

# Application log
sudo tail -f /var/www/garden-sensors/storage/logs/laravel.log

# Monitoring logs
sudo journalctl -u prometheus
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Version

Current version: **1.2.0**

## Deployment Features

### Automated Testing
- Full test suite runs automatically during deployment
- PHP unit tests (126 tests, 253 assertions)
- Python unit tests (35 tests)
- Deployment fails if tests fail (prevents broken deployments)

### Test Data Cleanup
- Automatic cleanup of test data after successful tests
- Removes test users, sensors, plants, and related data
- Verifies database is clean and production-ready
- Manual cleanup available via `database/cleanup_test_data.sql`

### Database Improvements
- Removed deprecated `fact_plants` table
- Consolidated to `plant_sensors` table with better structure
- Moisture thresholds moved to `plants` table
- Plant-sensor linking via `plant_sensors` table

### Interactive Plotting
- Plant-based interactive plots using Bokeh
- Filter by specific plant or view all plants
- Date range selection (7, 14, 30, 90 days)
- Real-time plot updates
- Zoom, pan, and hover tooltips
- API endpoint at `/api/plot.php`
- Python script: `python/generate_plot_api.py`

## MySQL Root Password
The MySQL root password is set to `newrootpassword`. **IMPORTANT:** This password should be changed in a production environment for security reasons.

## Interactive Plotting

The application includes interactive, plant-based data visualization powered by Bokeh.

### Features

- **Plant-Based Organization**: Sensor readings are organized by plant, not just sensor type
- **Interactive Filtering**: Filter plots by specific plant or view all plants
- **Date Range Selection**: View data for 7, 14, 30, or 90 days
- **Interactive Tools**: Zoom, pan, hover tooltips, and click-to-hide legend
- **Colorblind-Friendly Colors**: Each sensor type has a distinct color family (temperature=blue, humidity=green, moisture=orange, etc.)
- **Distinct Line Styles**: Different plants with the same sensor type use different shades and line dash patterns
- **Smart Legend Placement**: Legend positioned at bottom-left to avoid overlapping with data
- **Real-Time Updates**: Plots automatically refresh when filters change

### Accessing the Plots

1. Log into the dashboard
2. Navigate to the Dashboard page
3. Scroll to the "Sensor Readings Over Time" section
4. Use the filter dropdowns to select:
   - Plant (or "All Plants" for all)
   - Date range (7, 14, 30, or 90 days)
5. Click "Refresh" to update the plot

### Technical Details

- **Backend**: Python script (`python/generate_plot_api.py`) generates Bokeh plot components
- **API Endpoint**: `/api/plot.php` serves plot data as JSON
- **Frontend**: BokehJS renders interactive plots client-side
- **Database**: Joins `plants` → `plant_sensors` → `sensors` → `readings` tables

### Requirements

- Python 3.8+ with Bokeh library
- Bokeh installed: `pip install bokeh`
- Python virtual environment configured
- Database with plant-sensor relationships established

### API Usage

The plot API can be accessed programmatically:

```bash
# Get plot for all plants (last 7 days)
curl "http://localhost/garden-sensors/api/plot.php?days=7"

# Get plot for specific plant
curl "http://localhost/garden-sensors/api/plot.php?plant_id=1&days=30"

# Get plot data as JSON
curl "http://localhost/garden-sensors/api/plot.php?format=json&days=7"
```

## Next Steps: Containerization

The application is ready for Docker containerization. Planned features:
- Multi-container Docker Compose setup
- PHP 8.3 + Apache web container
- MySQL 8.0 database container
- Python 3.12 environment container
- Automated testing in containers
- Development and production configurations
