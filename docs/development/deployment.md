# Deployment Guide

## Prerequisites
- PHP 8.0 or higher
- Python 3.8 or higher
- MySQL 5.7 or higher
- Nginx or Apache web server
- SSL certificate (for production)

## Production Environment Setup

### 1. Server Preparation
```bash
# Update system packages
sudo apt update
sudo apt upgrade

# Install required packages
sudo apt install nginx mysql-server php8.0-fpm python3 python3-pip
```

### 2. Database Setup
```bash
# Create database and user
mysql -u root -p
CREATE DATABASE garden_sensors;
CREATE USER 'garden_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON garden_sensors.* TO 'garden_user'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Application Deployment
```bash
# Clone repository
git clone https://github.com/yourusername/garden-sensors.git
cd garden-sensors

# Install dependencies
composer install --no-dev
python3 -m pip install -r requirements.txt

# Set up environment
cp config/environment/.env.example config/environment/.env
# Edit .env file with production settings
```

### 4. Web Server Configuration

#### Nginx Configuration
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/garden-sensors/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 5. SSL Setup
```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx

# Obtain SSL certificate
sudo certbot --nginx -d your-domain.com
```

### 6. Security Measures
1. Set proper file permissions:
```bash
chmod -R 755 /path/to/garden-sensors
chmod -R 777 /path/to/garden-sensors/storage/logs
chmod -R 777 /path/to/garden-sensors/public/uploads
```

2. Configure firewall:
```bash
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

### 7. Monitoring Setup
1. Set up log rotation:
```bash
sudo nano /etc/logrotate.d/garden-sensors
```

Add the following:
```
/path/to/garden-sensors/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
}
```

### 8. Backup Strategy
1. Database backups:
```bash
# Create backup script
nano /path/to/garden-sensors/tools/backup.sh
```

Add the following:
```bash
#!/bin/bash
BACKUP_DIR="/path/to/backups"
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u garden_user -p garden_sensors > $BACKUP_DIR/db_backup_$DATE.sql
```

2. Set up cron job:
```bash
0 0 * * * /path/to/garden-sensors/tools/backup.sh
```

## Maintenance

### Regular Tasks
1. Update dependencies:
```bash
composer update
pip install -r requirements.txt --upgrade
```

2. Monitor logs:
```bash
tail -f /path/to/garden-sensors/storage/logs/error.log
```

3. Check disk space:
```bash
df -h
```

### Troubleshooting
1. Check error logs:
```bash
tail -f /var/log/nginx/error.log
tail -f /path/to/garden-sensors/storage/logs/error.log
```

2. Check PHP-FPM status:
```bash
systemctl status php8.0-fpm
```

3. Check MySQL status:
```bash
systemctl status mysql
```

## Rollback Procedure
1. Database rollback:
```bash
mysql -u garden_user -p garden_sensors < backup_file.sql
```

2. Code rollback:
```bash
git checkout <previous_version>
composer install --no-dev
``` 