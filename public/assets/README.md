# Garden Sensors GUI

A web-based dashboard for monitoring and managing garden sensors.

## Features

- Real-time sensor data visualization
- Plant health monitoring
- Sensor management
- Alert system
- User authentication
- Responsive design

## Requirements

- PHP 7.4 or higher
- Apache web server
- MySQL database
- Required PHP extensions:
  - mysqli
  - json
  - curl
  - gd
  - mbstring
  - xml
- Composer (for PHP dependencies)
- npm (for JavaScript dependencies)

## Installation

### Automatic Installation

The easiest way to install the Garden Sensors GUI is to use the provided setup script:

1. Make the setup script executable:
   ```bash
   chmod +x setup_gui.sh
   ```

2. Run the setup script as root:
   ```bash
   sudo ./setup_gui.sh
   ```

3. Follow the prompts to complete the installation.

The setup script will:
- Check system requirements
- Install dependencies
- Configure Apache
- Set up the database
- Set file permissions
- Install frontend dependencies
- Create a default admin user
- Set up cron jobs

### Manual Installation

If you prefer to install manually, follow these steps:

1. Install required packages:
   ```bash
   sudo apt-get update
   sudo apt-get install -y php-mysqli php-json php-curl php-gd php-mbstring php-xml
   ```

2. Install Composer:
   ```bash
   curl -sS https://getcomposer.org/installer | php
   sudo mv composer.phar /usr/local/bin/composer
   ```

3. Install npm:
   ```bash
   sudo apt-get install -y npm
   ```

4. Configure Apache:
   - Enable required modules: `sudo a2enmod rewrite headers`
   - Create a virtual host configuration
   - Restart Apache: `sudo systemctl restart apache2`

5. Set up the database:
   - Create a database named `garden_sensors`
   - Import the schema from `../database/schema.sql`
   - Create a database user with appropriate permissions

6. Set file permissions:
   ```bash
   sudo chown -R www-data:www-data /var/www/html/garden-sensors
   sudo find /var/www/html/garden-sensors -type d -exec chmod 755 {} \;
   sudo find /var/www/html/garden-sensors -type f -exec chmod 644 {} \;
   sudo chmod -R 775 /var/www/html/garden-sensors/cache
   sudo chmod -R 775 /var/www/html/garden-sensors/logs
   ```

7. Install frontend dependencies:
   ```bash
   npm install
   composer install
   ```

8. Create a default admin user in the database.

9. Set up cron jobs:
   ```
   */5 * * * * php /var/www/html/garden-sensors/cron/collect_readings.php
   */15 * * * * php /var/www/html/garden-sensors/cron/check_alerts.php
   0 0 * * * php /var/www/html/garden-sensors/cron/cleanup_data.php
   ```

## Usage

1. Access the Garden Sensors GUI at: http://localhost/garden-sensors
2. Log in with the admin credentials created during setup.
3. Navigate through the tabs to manage sensors, plants, and settings.

## Troubleshooting

### Common Issues

1. **Permission Denied Errors**
   - Ensure the web server user (www-data) has the correct permissions on the files and directories.
   - Check that the cache and logs directories are writable.

2. **Database Connection Errors**
   - Verify that the database credentials in `config/database.php` are correct.
   - Ensure the MySQL service is running.

3. **Apache Configuration Issues**
   - Check the Apache error logs for specific error messages.
   - Ensure the required Apache modules are enabled.

4. **PHP Extension Missing**
   - Install the missing PHP extension using apt-get.
   - Restart Apache after installing the extension.

### Getting Help

If you encounter issues not covered in this README, please:

1. Check the error logs in `/var/log/apache2/garden-sensors-error.log`
2. Look for specific error messages in the browser console
3. Contact the development team for assistance

## License

This project is licensed under the MIT License - see the LICENSE file for details. 