#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script to control water pumps based on soil moisture readings.
"""
import os
import logging
from datetime import datetime
from typing import Dict, Optional
from dataclasses import dataclass

import pandas as pd
import gpiozero as gpio
import time

import DBConnect as conct

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('garden_sensors.log'),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)

@dataclass
class Config:
    """Configuration settings for the pump control script."""
    db_name: str
    db_server: str
    db_username: str
    db_password: str
    pump_duration: int = 5  # seconds

def setup_config() -> Config:
    """Load configuration from environment variables or use defaults."""
    return Config(
        db_name=os.getenv('DB_NAME', 'SoilSensors'),
        db_server=os.getenv('DB_SERVER', 'SQL DB IP'),
        db_username=os.getenv('DB_USERNAME', 'SQLDB USERNAME'),
        db_password=os.getenv('DB_PASSWORD', 'SQLDB PASSWORD')
    )

class PumpController:
    """Class to handle pump control logic."""
    
    def __init__(self, config: Config):
        self.config = config
        self.cnx = conct.CFSQLConnect(
            config.db_name,
            config.db_username,
            config.db_password,
            config.db_server
        )
        
    def get_sensors(self) -> list:
        """Get list of all sensors."""
        query = "SELECT id as sensor_id FROM SoilSensors.Sensors"
        sensors = self.cnx.queryMySQL(query)
        return list(sensors.sensor_id)
    
    def get_latest_reading(self, sensor_id: int) -> Optional[Dict]:
        """Get the latest moisture reading for a sensor."""
        query = """
            SELECT Readings.reading, Readings.inserted 
            FROM SoilSensors.Readings 
            INNER JOIN SoilSensors.Sensors ON Readings.sensor_id = Sensors.id 
            WHERE Sensors.id = %s 
            ORDER BY Readings.inserted DESC limit 1;
        """
        reading = self.cnx.queryMySQL(query % sensor_id)
        if reading.empty:
            return None
        return {
            'reading': reading.reading.iloc[0],
            'timestamp': reading.inserted.iloc[0]
        }
    
    def get_plant_info(self, sensor_id: int) -> Optional[Dict]:
        """Get plant information for a sensor."""
        query = """
            SELECT lastWatered, plant_id, minSoilMoisture, maxSoilMoisture
            FROM SoilSensors.FactPlants fp
            JOIN SoilSensors.DimPlants dp ON fp.plant_id = dp.id
            WHERE fp.sensor_id = %s
        """
        info = self.cnx.queryMySQL(query % sensor_id)
        if info.empty:
            return None
        return {
            'last_watered': info.lastWatered.iloc[0],
            'plant_id': info.plant_id.iloc[0],
            'min_moisture': info.minSoilMoisture.iloc[0],
            'max_moisture': info.maxSoilMoisture.iloc[0]
        }
    
    def get_pin(self, sensor_id: int) -> Optional[int]:
        """Get GPIO pin number for a sensor."""
        query = "SELECT pin FROM SoilSensors.Pins WHERE sensor_id = %s"
        pin_info = self.cnx.queryMySQL(query % sensor_id)
        if pin_info.empty:
            return None
        return int(pin_info.pin.iloc[0])
    
    def update_watering_time(self, sensor_id: int, plant_id: int) -> None:
        """Update the last watering time for a plant."""
        query = """
            UPDATE SoilSensors.FactPlants 
            SET lastWatered = NOW() 
            WHERE sensor_id = %s AND plant_id = %s;
        """
        self.cnx.ExecuteMySQL(query % (sensor_id, plant_id))
    
    def control_pump(self, sensor_id: int) -> None:
        """Control pump for a specific sensor."""
        try:
            # Get latest reading
            reading = self.get_latest_reading(sensor_id)
            if not reading:
                logger.warning(f"No readings found for sensor {sensor_id}")
                return
            
            # Get plant information
            plant_info = self.get_plant_info(sensor_id)
            if not plant_info:
                logger.warning(f"No plant information found for sensor {sensor_id}")
                return
            
            # Get GPIO pin
            pin = self.get_pin(sensor_id)
            if pin is None:
                logger.warning(f"No GPIO pin found for sensor {sensor_id}")
                return
            
            # Check if watering is needed
            if (reading['timestamp'] > plant_info['last_watered'] and 
                reading['reading'] <= plant_info['min_moisture']):
                
                logger.info(f"Watering needed for sensor {sensor_id}")
                relay = gpio.LED(pin, active_high=False)
                
                try:
                    relay.on()
                    time.sleep(self.config.pump_duration)
                    relay.off()
                    self.update_watering_time(sensor_id, plant_info['plant_id'])
                    logger.info(f"Watering completed for sensor {sensor_id}")
                except Exception as e:
                    logger.error(f"Error controlling pump for sensor {sensor_id}: {e}")
                    relay.off()  # Ensure pump is turned off
                    raise
                
        except Exception as e:
            logger.error(f"Error processing sensor {sensor_id}: {e}")
            raise

def main() -> None:
    """Main function to control water pumps."""
    try:
        config = setup_config()
        controller = PumpController(config)
        
        # Get all sensors
        sensors = controller.get_sensors()
        if not sensors:
            logger.warning("No sensors found")
            return
        
        # Process each sensor
        for sensor_id in sensors:
            try:
                controller.control_pump(sensor_id)
            except Exception as e:
                logger.error(f"Failed to process sensor {sensor_id}: {e}")
                continue
                
    except Exception as e:
        logger.error(f"Fatal error in main: {e}")
        raise

if __name__ == "__main__":
    try:
        main()
    except Exception as e:
        logger.error(f"Script failed: {e}")
        raise
