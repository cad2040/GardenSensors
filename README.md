# Garden Sensors Application

A comprehensive garden monitoring system that tracks soil moisture, temperature, and other environmental factors using Arduino sensors and provides a web interface for data visualization and management.

## Features

- Real-time soil moisture and temperature monitoring
- Web-based dashboard for data visualization
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

## Installation

1. Clone the repository:
```bash
git clone https://github.com/yourusername/garden-sensors.git
cd garden-sensors
```

2. Set up environment variables:
```bash
cp .env.example .env
# Edit .env with your configuration
```

3. Run the setup script:
```bash
sudo ./setup.sh
```

The setup script will:
- Install all required system dependencies
- Set up Python virtual environment
- Configure MySQL database
- Deploy PHP application
- Configure Apache with SSL
- Set up firewall rules
- Configure monitoring with Prometheus
- Set up automated backups
- Configure rate limiting
- Create admin user

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
sudo ./setup.sh
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

5. Clean up local dev environment (if needed):
```bash
./dev_clean.sh
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
sudo chown -R www-data:www-data /var/www/garden-sensors
sudo chmod -R 755 /var/www/garden-sensors
```

2. Database Connection
```bash
sudo mysql -u garden_user -p
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

## Uninstallation

To completely remove the application:

```bash
sudo ./cleanup.sh
```

This will:
- Stop all services
- Remove application files
- Drop database
- Remove SSL certificates
- Remove firewall rules
- Remove monitoring components
- Clean up logs and caches

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## MySQL Root Password
The MySQL root password is set to `364828`. **IMPORTANT:** This password should be changed in a production environment for security reasons.
