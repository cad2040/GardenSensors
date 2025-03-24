# Garden Sensors Project

A comprehensive system for monitoring and managing garden sensors, plants, and environmental conditions.

## Features

- **Sensor Management**
  - Add, edit, and delete sensors
  - Monitor sensor status and battery levels
  - Track sensor readings and history
  - Export sensor data in CSV/JSON format

- **Plant Management**
  - Add, edit, and delete plants
  - Track plant moisture requirements
  - Monitor plant health status
  - Associate sensors with plants

- **Environmental Monitoring**
  - Real-time temperature monitoring
  - Soil moisture tracking
  - Battery level monitoring
  - Historical data analysis

- **Alert System**
  - Low battery alerts
  - Moisture level alerts
  - Temperature alerts
  - Email notifications
  - In-app notifications

- **User Features**
  - User authentication
  - Personal settings
  - Customizable alerts
  - Data export options
  - Theme customization

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Composer for dependency management
- Cron jobs enabled

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/yourusername/garden-sensors.git
   cd garden-sensors
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Create database and import schema:
   ```bash
   mysql -u your_username -p
   CREATE DATABASE garden_sensors;
   exit;
   mysql -u your_username -p garden_sensors < database/schema.sql
   ```

4. Configure the application:
   - Copy `config.example.php` to `config.php`
   - Update database credentials and other settings

5. Set up cron jobs:
   ```bash
   php GUI/cron/setup_cron.php
   ```

6. Set up default editor (optional):
   ```bash
   php GUI/cron/setup_editor.php
   ```

7. Set proper permissions:
   ```bash
   chmod -R 755 GUI/
   chmod -R 777 GUI/cache/
   chmod -R 777 GUI/logs/
   ```

## Directory Structure

```
garden-sensors/
├── database/
│   └── schema.sql
├── GUI/
│   ├── api/
│   │   └── controllers/
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   └── images/
│   ├── includes/
│   ├── cron/
│   └── logs/
└── config.php
```

## API Endpoints

### Sensor Management
- `POST /api/sensor/add` - Add new sensor
- `POST /api/sensor/edit` - Edit existing sensor
- `POST /api/sensor/delete` - Delete sensor
- `GET /api/sensor/readings` - Get sensor readings
- `GET /api/sensor/export` - Export sensor data

### Plant Management
- `POST /api/plant/add` - Add new plant
- `POST /api/plant/edit` - Edit existing plant
- `POST /api/plant/delete` - Delete plant
- `GET /api/plant/status` - Get plant status

### Reading Management
- `POST /api/reading/add` - Add new reading
- `POST /api/reading/delete` - Delete reading
- `GET /api/reading/export` - Export readings

### Settings Management
- `POST /api/settings/update` - Update user settings
- `POST /api/settings/reset` - Reset settings to default

## Cron Jobs

The following cron jobs are automatically set up:
- Check alerts every 5 minutes
- Clean up old data daily at midnight
- Optimize database weekly on Sunday at 2 AM

## Security Features

- CSRF protection
- Rate limiting
- Input validation
- SQL injection prevention
- XSS protection
- Secure session management
- User-specific data access

## Performance Optimization

- Database indexing
- Query optimization
- Caching system
- Regular cleanup of old data
- Log rotation
- Cache management

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support, please open an issue in the GitHub repository or contact the maintainers.

## Acknowledgments

- Thanks to all contributors who have helped shape this project
- Special thanks to the open-source community for various tools and libraries used
