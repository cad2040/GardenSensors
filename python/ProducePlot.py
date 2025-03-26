#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Created on Sat Sep  5 11:34:10 2020
Script to produce HTML plots from sensor data and upload via FTP to HASS RPi
@author: cad2040
"""
import os
import logging
import yaml
from datetime import datetime
from typing import Dict, List, Optional
from dataclasses import dataclass
from pathlib import Path
from tenacity import retry, stop_after_attempt, wait_exponential

from bokeh.plotting import figure, output_file
from bokeh.models import ColumnDataSource, DatetimeTickFormatter
from bokeh.resources import CDN
from bokeh.embed import file_html
from math import pi

import DBConnect as conct
import FTPConnectMod as FTPConnt

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
    """Configuration settings for the plot generation script."""
    db_name: str
    db_server: str
    db_username: str
    db_password: str
    ftp_address: str
    ftp_username: str
    ftp_password: str
    save_directory: str
    upload_path: str
    plot_width: int = 300
    plot_height: int = 400
    host_url: str = "http://HOST/"
    data_retention_days: int = 30
    max_retries: int = 3
    batch_size: int = 1000

    def validate(self) -> bool:
        """Validate configuration values."""
        required_fields = ['db_name', 'db_server', 'db_username', 'db_password',
                         'ftp_address', 'ftp_username', 'ftp_password']
        for field in required_fields:
            if not getattr(self, field):
                logger.error(f"Missing required configuration: {field}")
                return False
        return True

def load_config_file() -> Optional[Dict]:
    """Load configuration from YAML file."""
    config_path = Path('config.yaml')
    if config_path.exists():
        try:
            with open(config_path, 'r') as f:
                return yaml.safe_load(f)
        except Exception as e:
            logger.error(f"Failed to load config file: {e}")
    return None

def setup_config() -> Config:
    """Load configuration from YAML file, environment variables, or use defaults."""
    config_dict = load_config_file() or {}
    
    config = Config(
        db_name=config_dict.get('db_name') or os.getenv('DB_NAME', 'SoilSensors'),
        db_server=config_dict.get('db_server') or os.getenv('DB_SERVER', 'SQL DB IP'),
        db_username=config_dict.get('db_username') or os.getenv('DB_USERNAME', 'SQLDB USERNAME'),
        db_password=config_dict.get('db_password') or os.getenv('DB_PASSWORD', 'SQLDB PASSWORD'),
        ftp_address=config_dict.get('ftp_address') or os.getenv('FTP_ADDRESS', 'FTP IP'),
        ftp_username=config_dict.get('ftp_username') or os.getenv('FTP_USERNAME', 'FTP Uname'),
        ftp_password=config_dict.get('ftp_password') or os.getenv('FTP_PASSWORD', 'FTP Pass'),
        save_directory=config_dict.get('save_directory') or os.getenv('SAVE_DIR', os.path.join(os.getcwd(), 'plots')),
        upload_path=config_dict.get('upload_path') or os.getenv('UPLOAD_PATH', '//files//'),
        data_retention_days=config_dict.get('data_retention_days', 30),
        max_retries=config_dict.get('max_retries', 3),
        batch_size=config_dict.get('batch_size', 1000)
    )
    
    if not config.validate():
        raise ValueError("Invalid configuration")
    
    return config

@retry(stop=stop_after_attempt(3), wait=wait_exponential(multiplier=1, min=4, max=10))
def write_to_html(plot, name: str, directory: str) -> None:
    """Write plot to HTML file with retry logic."""
    try:
        fname = os.path.join(directory, name)
        html_str = file_html(plot, CDN, name)
        with open(fname, 'w') as html_file:
            html_file.write(html_str)
        logger.info(f"Successfully wrote plot to {fname}")
    except Exception as e:
        logger.error(f"Failed to write HTML file: {e}")
        raise

@retry(stop=stop_after_attempt(3), wait=wait_exponential(multiplier=1, min=4, max=10))
def plot_line(dataframe, x: str, y: str, title: str, fname: str, 
              xlabel: str, ylabel: str, directory: str, color: str = 'blue',
              width: int = 300, height: int = 400) -> figure:
    """Produce a line chart HTML file with retry logic."""
    try:
        fname = os.path.join(directory, fname)
        output_file(fname)
        ds = ColumnDataSource(dataframe)
        
        p = figure(plot_width=width, plot_height=height, title=title)
        p.line(source=ds, x=x, y=y, line_color=color,
               line_alpha=1.0, line_cap='butt')
        p.xaxis.axis_label = xlabel
        p.yaxis.axis_label = ylabel
        p.xaxis.formatter = DatetimeTickFormatter(
            hours=["%d %B %Y"],
            days=["%d %B %Y"],
            months=["%d %B %Y"],
            years=["%d %B %Y"]
        )
        p.xaxis.major_label_orientation = pi/4
        return p
    except Exception as e:
        logger.error(f"Failed to create plot: {e}")
        raise

def cleanup_files(files: List[str]) -> None:
    """Clean up temporary files."""
    for file in files:
        try:
            if os.path.exists(file):
                os.remove(file)
                logger.info(f"Cleaned up file: {file}")
        except Exception as e:
            logger.warning(f"Failed to clean up file {file}: {e}")

def process_sensor_data(cnx, sensor: str, data: pd.DataFrame, config: Config) -> Optional[str]:
    """Process data for a single sensor."""
    try:
        fname = f'Sensor{sensor}Plot.html'
        filepath = os.path.join(config.save_directory, fname)
        
        df = data[data.sensor == sensor]
        if df.empty:
            logger.warning(f"No data found for sensor {sensor}")
            return None
            
        plot = plot_line(
            df, 'inserted', 'reading', 'Moisture',
            fname, 'Datetime', 'Moisture Pct',
            config.save_directory, 'blue',
            config.plot_width, config.plot_height
        )
        
        write_to_html(plot, fname, config.save_directory)
        
        # Add URL to database
        sensor_id = data[data.sensor == sensor].sensor_id.iloc[0]
        url = f"{config.host_url}{fname}"
        query_insert_url = """
            INSERT INTO SoilSensors.Plots (sensor_id, sensor, URL)
            VALUES (%s, '%s', '%s')
        """
        cnx.ExecuteMySQL(query_insert_url % (sensor_id, sensor, url))
        
        return filepath
        
    except Exception as e:
        logger.error(f"Error processing sensor {sensor}: {e}")
        return None

def main() -> None:
    """Main function to generate and upload plots."""
    try:
        config = setup_config()
        
        # Ensure save directory exists
        os.makedirs(config.save_directory, exist_ok=True)
        
        # SQL queries
        query_sensors = "SELECT sensor FROM SoilSensors.Sensors"
        query_readings = """
            SELECT Sensors.sensor, Readings.reading, Readings.inserted, Readings.sensor_id 
            FROM SoilSensors.Readings 
            INNER JOIN SoilSensors.Sensors ON Readings.sensor_id = Sensors.id;
        """
        
        # Connect to database with retry
        @retry(stop=stop_after_attempt(config.max_retries), wait=wait_exponential(multiplier=1, min=4, max=10))
        def get_db_connection():
            return conct.CFSQLConnect(
                config.db_name,
                config.db_username,
                config.db_password,
                config.db_server
            )
        
        cnx = get_db_connection()
        
        # Get data
        data = cnx.queryMySQL(query_readings)
        sensors = list(cnx.queryMySQL(query_sensors).sensor.unique())
        
        # Generate and upload plots
        files = []
        cnx.ExecuteMySQL("TRUNCATE TABLE SoilSensors.Plots;")
        
        for sensor in sensors:
            filepath = process_sensor_data(cnx, sensor, data, config)
            if filepath:
                files.append(filepath)
                
                # Upload via FTP with retry
                @retry(stop=stop_after_attempt(config.max_retries), wait=wait_exponential(multiplier=1, min=4, max=10))
                def upload_file():
                    ftp_obj = FTPConnt.FTPConnectMod(
                        config.ftp_address,
                        config.ftp_username,
                        config.ftp_password
                    )
                    ftp_obj.UploadFile(
                        os.path.basename(filepath),
                        filepath,
                        config.upload_path
                    )
                
                upload_file()
        
        # Cleanup
        cleanup_files(files)
        
        # Remove old readings
        cnx.ExecuteMySQL(f"""
            DELETE FROM SoilSensors.Readings 
            WHERE inserted < (NOW() - INTERVAL {config.data_retention_days} DAY);
        """)
        
    except Exception as e:
        logger.error(f"Fatal error in main: {e}")
        raise

if __name__ == "__main__":
    try:
        main()
    except Exception as e:
        logger.error(f"Script failed: {e}")
        raise
