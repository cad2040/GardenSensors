# Garden Sensors Project

## Soil Moisture Sensor Wiring Diagram

```
ESP8266 (NodeMCU) Pinout:
                     _________________
                    |                 |
                    |    ESP8266      |
                    |                 |
                    |  [EN]     [RST] |
                    |                 |
                    |  [TX]     [RX]  |
                    |                 |
                    |  [D3]     [D1]  |
                    |                 |
                    |  [D4]     [D2]  |
                    |                 |
                    |  [D5]     [D0]  |
                    |                 |
                    |  [D6]     [D7]  |
                    |                 |
                    |  [D8]     [D9]  |
                    |                 |
                    |  [3V3]    [GND] |
                    |                 |
                    |  [VIN]    [GND] |
                    |                 |
                    |  [A0]     [D10] |
                    |                 |
                    |_________________|

Connections:
1. Soil Moisture Sensor (analog, existing):
   - VCC → 3V3
   - GND → GND
   - AOUT → A0

2. RS485 soil probe(s) (pH / EC nutrient proxy) via MAX485:
   - Probe VCC → **5 V** from USB / solar power bank (same rail as NodeMCU VIN/USB)
   - Probe GND → common GND with ESP + MAX485 + bank
   - Probe A / B → MAX485 A / B
   - MAX485 VCC → 3V3 (or 5 V if the module requires it; level-shift if needed)
   - MAX485 RO → ESP **D7** (GPIO13) RX
   - MAX485 DI → ESP **D6** (GPIO12) TX
   - MAX485 RE + DE (tied together) → ESP **D5** (GPIO14) direction control

3. LED Indicator (Built-in):
   - Connected to D2 (GPIO2)
   - Note: Built-in LED is active LOW

4. Deep Sleep Connection:
   - Connect D0 (GPIO16) to RST
   - This enables automatic wake-up from deep sleep

5. Power (5 V USB / solar bank):
   - VIN / USB → 5 V from always-on USB power bank
   - Probe VCC → same 5 V rail (not ESP 3V3)
   - GND → GND (common)

Note: The built-in LED on most ESP8266 boards is connected to GPIO2 (D2) and is active LOW.
This means the LED turns ON when the pin is LOW and OFF when the pin is HIGH.
```

## Recommended hardware (sensor build)

### Soil probes (must be DC 5–30 V for USB banks)

| Role | Recommendation | Notes |
|------|----------------|-------|
| pH (+ moisture/temp/EC) | [DFRobot SEN0604](https://wiki.dfrobot.com/sen0604/) RS485 4-in-1 | Rated **5–30 V**; Modbus RTU; IP68 burial probe |
| EC (nutrient / salt proxy) | Same RS485 probe (7-in-1 or SEN0604) | Soil EC in **mS/cm**; dashboard uses **available EC** = soil EC − your tap-water baseline (set in Settings) |
| Optional NPK probe | [DFRobot SEN0605](https://wiki.dfrobot.com/sen0605/) RS485 NPK | Not used by GardenSensors firmware; EC trend is the fertilizer signal instead of N mg/kg |
| Combined alternative | Generic “7-in-1” RS485 Modbus soil probe (5–30 V) | One probe for moisture/temp/EC/pH/NPK; verify datasheet minimum is **5 V**, not 9–24 V only |
| Existing moisture | Capacitive soil moisture (A0) | Keep as today; 3V3 analog |

**Modbus EC register (firmware):** holding register `0x0002`, scale ×0.01 → mS/cm (verify on your probe datasheet). Calibrate **baseline_water_ec** by measuring a cup of tap water and entering that value in Settings.

**RS485 adapter:** MAX485 / TTL-to-RS485 breakout (one bus per probe address).

Typical Modbus defaults: device address `0x01`, baud **4800** or **9600** (check the probe datasheet). Allow **5–10 seconds** after power-on for pH readings to settle before sampling.

### Power banks (solar-chargeable USB, 5 V)

| Recommendation | Why |
|----------------|-----|
| [Voltaic Systems](https://voltaicsystems.com/always-on-batteries/) V25 / V50 / V75 (or V88 with Always On mode) | Designed for IoT; **no low-current auto-off**; pairs with Voltaic 6 V solar panels |
| Any bank marketed **“always on” / “no auto-off” / “router / IoT mode”** | Required for ESP deep sleep — most phone banks shut off under microamp loads |
| Avoid | Generic phone power banks without always-on — they cut 5 V while the NodeMCU sleeps |

**Power notes:**

- Share the bank’s **5 V USB** with NodeMCU VIN/USB and probe VCC; use a common GND.
- Do **not** power RS485 probes from ESP **3V3**.
- If stuck with a normal bank, a pulsed keep-alive load can prevent cutoff but wastes energy — prefer a true always-on pack for solar.
- Budget wake time for probe settle plus WiFi/MySQL before deep sleep.

## Features

- Soil moisture, pH, and **soil EC** (nutrient proxy) monitoring
- WiFi connectivity with automatic reconnection
- MySQL data storage
- Web interface for monitoring and configuration
- Deep sleep power management
- LED status indicators
- Watchdog timer protection
- EEPROM configuration storage
- mDNS support for easy device discovery
- Basic web authentication

## LED Status Indicators

The built-in LED provides visual feedback about the device's status:
- Solid ON: Connecting to WiFi
- OFF: Normal operation
- Rapid blinking: Error condition
- Slow blinking: Configuration mode

## Web Interface

### Accessing the Interface
1. Connect to the device's IP address in a web browser
   - Direct IP: `http://[device-ip]`
   - mDNS: `http://[hostname].local` (e.g., `http://Sector01Sensor.local`)

### Default Credentials
- Username: `admin`
- Password: `admin`

### Available Pages

1. Status Page (`/`):
   - Last moisture, pH, and soil EC readings
   - Last reading time
   - WiFi signal strength
   - Device uptime
   - Wake-up reason
   - Link to configuration

2. Configuration Page (`/config`):
   - WiFi SSID
   - WiFi Password
   - Save configuration option

## Power Management

The device uses deep sleep to conserve power:
1. Wakes up every hour (configurable)
2. Takes readings
3. Uploads data
4. Returns to deep sleep

## Configuration

### Initial Setup
1. Flash the firmware to your ESP8266
2. Connect the components as per the wiring diagram
3. Power on the device
4. If WiFi connection fails, the device enters configuration mode
5. Access the web interface to configure WiFi settings

### EEPROM Storage
- Configuration is stored in EEPROM
- Settings persist across power cycles
- Update settings through the web interface

### Calibration
Default moisture mapping values:
- Dry soil: 270
- Wet soil: 732

Adjust these values in the code if needed:
```cpp
moisture_min = 270;  // Value for dry soil
moisture_max = 732;  // Value for wet soil
```

## Troubleshooting

1. If the device doesn't wake up:
   - Check the D0 to RST connection
   - Verify power supply is stable

2. If readings are inconsistent:
   - Check sensor connections
   - Verify sensor is properly inserted in soil
   - Calibrate sensor values if needed

3. If WiFi connection fails:
   - Verify credentials in configuration
   - Check signal strength
   - Ensure router is operational

4. If MySQL connection fails:
   - Verify server IP and credentials
   - Check network connectivity
   - Ensure MySQL server is running

5. If web interface is inaccessible:
   - Check device IP address
   - Verify WiFi connection
   - Try accessing via mDNS name

## Development

### Required Libraries
- ESP8266WiFi
- MySQL_Connection
- MySQL_Cursor
- ESP8266WebServer
- ESP8266mDNS
- EEPROM

### Building
1. Install required libraries through Arduino IDE Library Manager
2. Select correct board (ESP8266)
3. Upload the sketch

### Customization
- Modify `config` structure for additional settings
- Adjust timing parameters in configuration
- Customize web interface HTML/CSS
- Modify LED behavior in `indicateStatus()`

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

2. Set up Python virtual environment:
   ```bash
   # Install python3-venv if not already installed
   sudo apt-get install python3-venv

   # Create and activate virtual environment
   python3 -m venv venv
   source venv/bin/activate  # On Windows: venv\Scripts\activate

   # Install Python dependencies
   pip install -r requirements.txt
   ```

3. Set up MySQL database:
   ```bash
   # Login to MySQL
   mysql -u root -p

   # Create database and user
   CREATE DATABASE SoilSensors;
   CREATE USER 'SoilSensors'@'localhost' IDENTIFIED BY 'your_password';
   GRANT ALL PRIVILEGES ON SoilSensors.* TO 'SoilSensors'@'localhost';
   FLUSH PRIVILEGES;
   exit;

   # Import database schema
   mysql -u SoilSensors -p SoilSensors < database/schema.sql
   ```

4. Configure the application:
   ```bash
   # Copy example configuration file
   cp config.example.php config.php

   # Edit config.php with your settings:
   # - Database credentials
   # - FTP settings
   # - Email notification settings
   # - Other custom configurations
   ```

5. Set up web server (Apache):
   ```bash
   # Install Apache and PHP if not already installed
   sudo apt-get install apache2 php php-mysql

   # Copy project files to web directory
   sudo cp -r GUI/* /var/www/html/

   # Set proper permissions
   sudo chown -R www-data:www-data /var/www/html/
   sudo chmod -R 755 /var/www/html/
   sudo chmod -R 777 /var/www/html/cache/
   sudo chmod -R 777 /var/www/html/logs/
   ```

6. Configure Apache:
   ```bash
   # Enable required Apache modules
   sudo a2enmod rewrite
   sudo a2enmod php

   # Restart Apache
   sudo systemctl restart apache2
   ```

7. Set up cron jobs:
   ```bash
   # Open crontab editor
   crontab -e

   # Add the following lines:
   # Check alerts every 5 minutes
   */5 * * * * php /var/www/html/cron/check_alerts.php
   
   # Clean up old data daily at midnight
   0 0 * * * php /var/www/html/cron/cleanup.php
   
   # Optimize database weekly on Sunday at 2 AM
   0 2 * * 0 php /var/www/html/cron/optimize_db.php
   ```

8. Set up ESP8266 devices:
   ```bash
   # Install Arduino IDE
   # Add ESP8266 board support:
   # 1. Open Arduino IDE
   # 2. Go to File > Preferences
   # 3. Add to Additional Boards Manager URLs:
   #    http://arduino.esp8266.com/stable/package_esp8266com_index.json
   # 4. Go to Tools > Board > Boards Manager
   # 5. Search for "esp8266" and install

   # Upload sketch to ESP8266:
   # 1. Select correct board (Tools > Board > ESP8266 Boards > NodeMCU 1.0)
   # 2. Select correct port
   # 3. Upload sketch_SoilSensor.ino
   ```

9. Initial configuration of ESP8266:
   - Power on the device
   - Connect to the "GardenSensor_AP" WiFi network
   - Open web browser and navigate to http://192.168.4.1
   - Enter your WiFi credentials
   - Configure sensor settings:
     - Sensor name
     - Reading interval
     - Moisture thresholds
   - Save configuration

10. Verify installation:
    ```bash
    # Check web interface
    open http://localhost in your browser
    
    # Check database connection
    php GUI/test_db.php
    
    # Check sensor readings
    php GUI/test_sensors.php
    ```

## Configuration Files

### config.php
```php
// Database settings
define('DB_HOST', 'localhost');
define('DB_NAME', 'SoilSensors');
define('DB_USER', 'SoilSensors');
define('DB_PASS', 'your_password');

// FTP settings
define('FTP_HOST', 'your_ftp_host');
define('FTP_USER', 'your_ftp_user');
define('FTP_PASS', 'your_ftp_pass');
define('FTP_PATH', '/public_html/plots/');

// Email settings
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your_email@gmail.com');
define('SMTP_PASS', 'your_app_password');
define('ALERT_EMAIL', 'alerts@yourdomain.com');

// Application settings
define('DATA_RETENTION_DAYS', 30);
define('ALERT_THRESHOLD', 20); // Low battery percentage
define('REFRESH_INTERVAL', 300); // 5 minutes
```

### config.h (ESP8266)
```cpp
// WiFi settings
const char* ssid = "your_wifi_ssid";
const char* password = "your_wifi_password";

// Server settings
const char* serverUrl = "http://your-server/insertReading.php";
const char* apiKey = "your_api_key";

// Sensor settings
const int moisture_min = 270;  // Value for dry soil
const int moisture_max = 732;  // Value for wet soil
const int reading_interval = 3600;  // 1 hour in seconds
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
