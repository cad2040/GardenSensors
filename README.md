# Garden Sensors Project 🌱

## Overview
A comprehensive garden monitoring system that uses various sensors to track soil moisture, temperature, and humidity. The system provides real-time data visualization, automated alerts, and remote control capabilities through a modern web interface.

## Features 🌟
- **Real-time Monitoring**
  - Soil moisture tracking
  - Temperature monitoring
  - Humidity measurement
  - Automated data collection

- **Smart Alerts**
  - Low moisture warnings
  - Temperature thresholds
  - Battery status monitoring
  - System health notifications

- **Automation**
  - Scheduled watering
  - Custom watering schedules per plant
  - Automated data cleanup
  - Database optimization

- **Security**
  - User authentication
  - Role-based access control
  - API rate limiting
  - CSRF protection
  - Input validation
  - Secure password handling

- **Performance**
  - Database partitioning
  - Query optimization
  - Caching system
  - Efficient data storage

## System Requirements 🖥️
- PHP 7.4 or higher
- MySQL 8.0 or higher
- Python 3.8 or higher
- Apache 2.4 or higher
- Composer
- Git
- (Optional) Arduino IDE or Arduino CLI for sensor development

## Quick Start 🚀

### 1. Clone the Repository
```bash
git clone https://github.com/yourusername/garden-sensors.git
cd garden-sensors
```

### 2. Environment Setup
```bash
# Copy environment file
cp .env.example .env

# Edit .env with your settings
nano .env
```

### 3. Installation
```bash
# Install PHP dependencies
composer install

# Install Python dependencies
python3 -m venv venv
source venv/bin/activate  # On Windows: venv\Scripts\activate
pip install -r requirements.txt

# Run setup script
sudo ./setup.sh
```

### 4. Arduino Development (Optional)
If you're developing or modifying the sensor code:
```bash
# Install Arduino dependencies
pip install -r requirements-arduino.txt

# Set Arduino development flag
export ARDUINO_DEVELOPMENT=true

# Run setup script again
sudo ./setup.sh
```

### 5. Database Setup
```bash
# Import database schema
mysql -u root -p < SQLDeployScript.sql
```

### 6. Start the Application
```bash
# Development server
php -S localhost:8000 -t public

# Production (using Apache)
sudo systemctl restart apache2
```

## Project Structure 📁
```
GardenSensors/
├── arduino/           # Arduino sensor code
├── config/           # Configuration files
├── database/         # Database migrations and seeds
├── docs/            # Documentation
├── logs/            # Application logs
├── public/          # Public web files
├── python/          # Python scripts
├── src/             # Source code
│   ├── Core/        # Core functionality (Config, Utils)
│   ├── Controllers/ # Controller classes
│   ├── Models/      # Model classes
│   ├── Services/    # Service classes
│   ├── Exceptions/  # Custom exceptions
│   ├── Interfaces/  # Interface definitions
│   └── Views/       # View templates
├── tests/           # Test files
├── tools/           # Development tools
├── uploads/         # User uploads
├── vendor/          # PHP dependencies
├── .env             # Environment variables
├── .env.example     # Example environment variables
├── composer.json    # PHP dependencies
├── requirements.txt # Python dependencies
└── SQLDeployScript.sql # Database schema
```

## Development 🛠️

### Code Style
The project follows PSR-4 autoloading standards and modern PHP features:
- Type declarations
- Return type declarations
- Null coalescing operator
- Arrow functions
- Match expressions
- Named arguments
- Constructor property promotion

### Testing
We have comprehensive test suites for both PHP and Python components. For detailed testing information, see our [Testing Guide](docs/development/TESTING.md).

#### Quick Start Testing
```bash
# PHP Tests
composer test                    # Run all PHP tests
composer test-coverage          # Generate coverage report
./vendor/bin/phpunit --testsuite Models    # Run specific test suite

# Python Tests
python3 tests/test_sensor_simulator.py     # Run sensor tests
python3 tests/test_sensor_simulator.py --generate  # Generate test data

# Cleanup Test Data
./tests/cleanup_test_data.sh    # Clean up test files and database
```

#### Test Requirements
- PHPUnit 9.0 or higher
- Python unittest module
- MySQL test database
- Mock hardware capabilities

#### Code Coverage
- Minimum 80% coverage required
- Coverage reports in `tests/coverage/`
- Critical paths require 100% coverage

### Database Management
```bash
# Backup database
./tools/backup_db.sh

# Optimize database
php tools/optimize_db.php

# Clean old data
php tools/cleanup.php
```

## Monitoring 📊
- Access the web interface at: `http://localhost/garden-sensors`
- View system health at: `http://localhost/garden-sensors/health`
- Check logs at: `logs/application.log`

## Troubleshooting 🔧
1. Check the logs in `logs/` directory
2. Verify database connection in `.env`
3. Ensure all required services are running
4. Check file permissions
5. Review the [Troubleshooting Guide](docs/troubleshooting.md)

## Contributing 🤝
Please read our [Contributing Guide](docs/development/CONTRIBUTING.md) for details on:
- Code of conduct
- Development process
- Pull request process
- Coding standards

## Security 🔒
- Report security issues to: security@yourdomain.com
- Follow our [Security Policy](SECURITY.md)
- Keep dependencies updated
- Use secure passwords

## License 📄
This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support 💬
- Documentation: [docs/](docs/)
- Issues: [GitHub Issues](https://github.com/yourusername/garden-sensors/issues)
- Email: support@yourdomain.com

## Acknowledgments 🙏
- Thanks to all contributors
- Built with [PHP](https://php.net)
- Powered by [MySQL](https://mysql.com)
- Visualized with [Bokeh](https://bokeh.org)
