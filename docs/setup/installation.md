# Garden Sensors Setup Guide

This guide details the steps to set up the Garden Sensors application manually.

## Prerequisites

- Apache2
- PHP 8.x
- MySQL 8.x
- Python 3.x
- Required PHP extensions:
  - mysqli
  - json
  - curl
  - mbstring
  - xml
  - zip

## Step-by-Step Installation

### 1. Database Setup

```bash
# Create database and user
sudo mysql -e "CREATE DATABASE IF NOT EXISTS SoilSensors;"
sudo mysql -e "CREATE USER IF NOT EXISTS 'SoilSensors'@'localhost' IDENTIFIED BY 'SoilSensors123';"
sudo mysql -e "GRANT ALL PRIVILEGES ON SoilSensors.* TO 'SoilSensors'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

# Import database schema
sudo mysql SoilSensors < database/schema.sql
```

### 2. Web Server Setup

```bash
# Create web directory
sudo mkdir -p /var/www/html/garden-sensors
sudo mkdir -p /var/www/html/garden-sensors/cache
sudo mkdir -p /var/www/html/garden-sensors/logs

# Copy application files
sudo cp -r GUI/* /var/www/html/garden-sensors/

# Set permissions
sudo chown -R www-data:www-data /var/www/html/garden-sensors/
sudo chmod -R 755 /var/www/html/garden-sensors/
sudo chmod -R 777 /var/www/html/garden-sensors/cache/
sudo chmod -R 777 /var/www/html/garden-sensors/logs/
```

### 3. Apache Configuration

Create a new virtual host configuration file at `/etc/apache2/sites-available/garden-sensors.conf`:

```apache
<VirtualHost *:80>
    ServerName garden-sensors.local
    DocumentRoot /var/www/html/garden-sensors
    
    <Directory /var/www/html/garden-sensors>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/garden-sensors-error.log
    CustomLog ${APACHE_LOG_DIR}/garden-sensors-access.log combined
</VirtualHost>
```

Enable the site and restart Apache:

```bash
sudo a2ensite garden-sensors.conf
sudo systemctl reload apache2
```

### 4. Python Environment Setup

```bash
# Create virtual environment
python3 -m venv venv

# Install dependencies
./venv/bin/pip install -r requirements.txt
```

### 5. Create Admin User

The default admin user will be created with these credentials:
- Username: `admin`
- Password: `password`
- Email: `admin@example.com`

```bash
# Create admin user in database
sudo mysql -e "USE SoilSensors; INSERT INTO users (username, password, email, role, created_at) VALUES ('admin', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com', 'admin', NOW()) ON DUPLICATE KEY UPDATE password = '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';"
```

## Verification

1. Check Apache status:
```bash
systemctl status apache2
```

2. Check MySQL status:
```bash
systemctl status mysql
```

3. Test web access:
```bash
curl -I http://localhost/garden-sensors/
```
You should see a 302 redirect to login.php

## Accessing the Application

1. Open your web browser and navigate to:
```
http://localhost/garden-sensors
```

2. Log in with the default admin credentials:
- Username: `admin`
- Password: `password`

## Default Settings

The following default settings are configured in the database:
- Reading interval: 3600 seconds (1 hour)
- Alert threshold: 20% (battery level)
- Data retention: 30 days
- Email notifications: enabled

## Directory Structure

```
/var/www/html/garden-sensors/
├── cache/           # Cache directory (777 permissions)
├── logs/            # Logs directory (777 permissions)
├── css/            # Stylesheets
├── js/             # JavaScript files
├── includes/       # PHP includes
└── cron/           # Cron job scripts
```

## Troubleshooting

1. If you see permission errors in the logs:
```bash
sudo chown -R www-data:www-data /var/www/html/garden-sensors/
sudo chmod -R 755 /var/www/html/garden-sensors/
```

2. If the database connection fails:
```bash
sudo mysql -u SoilSensors -pSoilSensors123 -e "USE SoilSensors;"
```

3. To check Apache error logs:
```bash
sudo tail -f /var/log/apache2/garden-sensors-error.log
```

4. To check application logs:
```bash
sudo tail -f /var/www/html/garden-sensors/logs/app.log
```

## Security Notes

1. The default admin password should be changed immediately after first login
2. MySQL credentials are stored in the application's config file
3. Log and cache directories have 777 permissions - ensure they're properly secured
4. Consider setting up SSL/TLS for production use

## Next Steps

1. Configure your ESP8266 devices according to the README.md instructions
2. Update the email settings in the configuration if you want to receive alerts
3. Add your plants and sensors through the web interface
4. Monitor the system through the dashboard 