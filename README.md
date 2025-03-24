# GardenSensors

A comprehensive garden monitoring and automation system that uses soil moisture sensors to monitor plant health and automatically water plants when needed.

## Features

- Soil moisture monitoring using ESP8266 and capacitive soil moisture sensors
- Automatic plant watering based on moisture thresholds
- Data storage in MySQL database
- Beautiful visualization using Bokeh plots
- Home Assistant integration via FTP
- Configurable settings via environment variables
- Robust error handling and logging

## Hardware Requirements

- ESP8266 (e.g., NodeMCU, Wemos D1 Mini)
- Capacitive Soil Moisture Sensor
- Water pump with relay control
- Raspberry Pi (for running the Python scripts and hosting Home Assistant)

## Software Requirements

- Python 3.8+
- MySQL Server
- Home Assistant (optional, for visualization)

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/yourusername/GardenSensors.git
   cd GardenSensors
   ```

2. Install Python dependencies:
   ```bash
   pip install -r requirements.txt
   ```

3. Configure environment variables:
   ```bash
   cp .env.example .env
   ```
   Edit the `.env` file with your specific settings.

4. Set up the MySQL database:
   ```bash
   mysql -u your_username -p < SQLDeployScript.sql
   ```

5. Upload the Arduino code to your ESP8266:
   - Open `sketch_SoilSensor/sketch_SoilSensor.ino` in Arduino IDE
   - Install required libraries (ESP8266WiFi, MySQL_Connection)
   - Update WiFi and MySQL credentials
   - Upload to your ESP8266

## Configuration

### Environment Variables

The following environment variables can be configured in the `.env` file:

- `DB_NAME`: MySQL database name
- `DB_SERVER`: MySQL server IP address
- `DB_USERNAME`: MySQL username
- `DB_PASSWORD`: MySQL password
- `FTP_ADDRESS`: FTP server IP address
- `FTP_USERNAME`: FTP username
- `FTP_PASSWORD`: FTP password
- `SAVE_DIR`: Directory for saving plot files
- `UPLOAD_PATH`: FTP upload path
- `HOST_URL`: Base URL for accessing plots

### Sensor Configuration

1. Add your sensors to the `SoilSensors.Sensors` table
2. Configure GPIO pins in the `SoilSensors.Pins` table
3. Add plant information to `SoilSensors.DimPlants`
4. Link sensors to plants in `SoilSensors.FactPlants`

## Usage

1. Start the soil moisture monitoring:
   ```bash
   python python/ProducePlot.py
   ```

2. Start the automatic watering system:
   ```bash
   python python/RunPump.py
   ```

3. View the plots in Home Assistant using iframes pointing to the generated HTML files.

## Logging

Logs are written to `garden_sensors.log` in the project root directory. The log file contains:
- Sensor readings
- Pump control actions
- Error messages
- System status updates

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Acknowledgments

- ESP8266 community for the excellent libraries
- Bokeh team for the visualization library
- Home Assistant community for the integration possibilities
